<?php

namespace BristolDigital\QwikBlog\Services;

use BristolDigital\QwikBlog\ValueObjects\BlogPost;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogService
{
    private string $postsPath;

    public function __construct()
    {
        $this->postsPath = config('qwikblog.posts_path') ?: resource_path('posts');
    }

    public function getAllPosts(): Collection
    {
        $posts = Cache::remember(
            'blog.all_posts',
            (int) config('qwikblog.cache_duration', 3600),
            fn() => $this->loadPostsFromDisk()
        );

        // Self-heal stale cache. The cache may hold __PHP_Incomplete_Class
        // (single object) if a previous version of the code stored something
        // else under this key, or a Collection of incomplete BlogPost
        // instances if the package was previously installed under a different
        // namespace. The instanceof check on Collection short-circuits before
        // we'd touch a non-Collection — calling methods on
        // __PHP_Incomplete_Class would throw, which is the failure mode the
        // earlier (less defensive) version of this code suffered from.
        $needsRebuild = ! $posts instanceof Collection
            || ($posts->isNotEmpty() && ! $posts->first() instanceof BlogPost);

        if ($needsRebuild) {
            Cache::forget('blog.all_posts');
            $posts = $this->loadPostsFromDisk();
            Cache::put(
                'blog.all_posts',
                $posts,
                (int) config('qwikblog.cache_duration', 3600)
            );
        }

        return $posts;
    }

    private function loadPostsFromDisk(): Collection
    {
        File::ensureDirectoryExists($this->postsPath);

        $files = File::glob($this->postsPath . '/*.md');

        return collect($files)
            ->map(fn($file) => BlogPost::fromFile($file))
            ->sortByDesc('date')
            ->values();
    }

    public function getPublishedPosts(): Collection
    {
        return $this->getAllPosts()
            ->filter(fn($p) => $p->isPublished())
            ->values();
    }

    public function getPostBySlug(string $slug): ?BlogPost
    {
        return $this->getAllPosts()->firstWhere('slug', $slug);
    }

    public function getPublishedPostBySlug(string $slug): ?BlogPost
    {
        return $this->getPublishedPosts()->firstWhere('slug', $slug);
    }

    public function getAdjacentPublishedPosts(BlogPost $currentPost): array
    {
        $posts = $this->getPublishedPosts();
        $currentIndex = $posts->search(fn($post) => $post->slug === $currentPost->slug);

        if ($currentIndex === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $posts->get($currentIndex - 1),
            'next' => $posts->get($currentIndex + 1),
        ];
    }

    /* ---------------------------------------------------------------------
     | Taxonomy
     | -------------------------------------------------------------------*/

    public function getAllCategories(): array
    {
        return $this->collectFrom($this->getAllPosts(), 'categories');
    }

    public function getAllTags(): array
    {
        return $this->collectFrom($this->getAllPosts(), 'tags');
    }

    public function getPublishedCategories(): array
    {
        return $this->collectFrom($this->getPublishedPosts(), 'categories');
    }

    public function getPublishedTags(): array
    {
        return $this->collectFrom($this->getPublishedPosts(), 'tags');
    }

    public function getPublishedCategoryCounts(): array
    {
        return $this->countTermsIn($this->getPublishedPosts(), 'categories');
    }

    public function getPublishedTagCounts(): array
    {
        return $this->countTermsIn($this->getPublishedPosts(), 'tags');
    }

    public function getPublishedAuthors(): array
    {
        return $this->getPublishedPosts()
            ->pluck('author')
            ->filter(fn($a) => is_string($a) && trim($a) !== '')
            ->unique()
            ->sort(SORT_FLAG_CASE | SORT_NATURAL)
            ->values()
            ->all();
    }

    /* ---------------------------------------------------------------------
     | Archive
     | -------------------------------------------------------------------*/

    /**
     * Hierarchical archive map of published posts:
     *   [2024 => [11 => 5, 10 => 3, 9 => 2], 2023 => [12 => 4, ...]]
     *
     * Years sorted desc (newest first), months within each year also desc.
     * Returns [] when no published posts. Empty when called means the view
     * should hide the archive nav entirely — that's the "no archive nav
     * unless there are some" convention. Views can apply a stricter
     * threshold (e.g. only show when 2+ months are represented) by counting
     * the entries themselves; the service just returns the raw shape.
     *
     * @return array<int,array<int,int>>
     */
    public function getPublishedArchive(): array
    {
        $archive = [];
        foreach ($this->getPublishedPosts() as $post) {
            $year = (int) $post->date->year;
            $month = (int) $post->date->month;
            $archive[$year][$month] = ($archive[$year][$month] ?? 0) + 1;
        }

        krsort($archive);
        foreach ($archive as &$months) {
            krsort($months);
        }

        return $archive;
    }

    /**
     * Posts published in a given year, optionally narrowed to a month.
     */
    public function getPostsByDate(int $year, ?int $month = null): Collection
    {
        return $this->getPublishedPosts()
            ->filter(function (BlogPost $post) use ($year, $month) {
                if ((int) $post->date->year !== $year) {
                    return false;
                }
                if ($month !== null && (int) $post->date->month !== $month) {
                    return false;
                }
                return true;
            })
            ->values();
    }

    /* ---------------------------------------------------------------------
     | Search
     | -------------------------------------------------------------------*/

    public function searchPublishedPosts(string $query): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $terms = preg_split('/\s+/', mb_strtolower($query)) ?: [];
        $terms = array_filter($terms, fn($t) => $t !== '');
        if (empty($terms)) {
            return collect();
        }

        return $this->getPublishedPosts()
            ->filter(function (BlogPost $post) use ($terms) {
                $haystack = mb_strtolower(implode(' ', [
                    $post->title,
                    $post->subtitle,
                    $post->summary,
                    $post->author,
                    implode(' ', $post->categories),
                    implode(' ', $post->tags),
                    strip_tags($post->content),
                ]));

                foreach ($terms as $term) {
                    if (!str_contains($haystack, $term)) {
                        return false;
                    }
                }
                return true;
            })
            ->values();
    }

    /* ---------------------------------------------------------------------
     | Related posts
     | -------------------------------------------------------------------*/

    /**
     * Tag and category weights are configurable via qwikblog.related.*.
     * Default 2:1 — tags are more discriminating per shared term.
     */
    public function getRelatedPosts(BlogPost $post, ?int $limit = null): Collection
    {
        $tagWeight = (int) config('qwikblog.related.tag_weight', 2);
        $categoryWeight = (int) config('qwikblog.related.category_weight', 1);
        $limit ??= (int) config('qwikblog.related.limit', 3);

        return $this->getPublishedPosts()
            ->reject(fn(BlogPost $p) => $p->slug === $post->slug)
            ->map(function (BlogPost $p) use ($post, $tagWeight, $categoryWeight) {
                $sharedCats = count(array_intersect($post->categories, $p->categories));
                $sharedTags = count(array_intersect($post->tags, $p->tags));
                $p->relatedScore = ($sharedTags * $tagWeight) + ($sharedCats * $categoryWeight);
                return $p;
            })
            ->filter(fn(BlogPost $p) => $p->relatedScore > 0)
            ->sortByDesc(fn(BlogPost $p) => [$p->relatedScore, $p->date->timestamp])
            ->take($limit)
            ->values();
    }

    /* ---------------------------------------------------------------------
     | Internal helpers
     | -------------------------------------------------------------------*/

    private function collectFrom(Collection $posts, string $field): array
    {
        return $posts
            ->pluck($field)
            ->flatten()
            ->filter(fn($v) => is_string($v) && trim($v) !== '')
            ->unique()
            ->sort(SORT_FLAG_CASE | SORT_NATURAL)
            ->values()
            ->all();
    }

    private function countTermsIn(Collection $posts, string $field): array
    {
        $counts = [];
        foreach ($posts as $post) {
            foreach ($post->{$field} ?? [] as $term) {
                if (!is_string($term) || trim($term) === '') {
                    continue;
                }
                $counts[$term] = ($counts[$term] ?? 0) + 1;
            }
        }
        ksort($counts, SORT_FLAG_CASE | SORT_NATURAL);
        return $counts;
    }

    public function clearCache(): void
    {
        Cache::forget('blog.all_posts');
    }

    /* ---------------------------------------------------------------------
     | CRUD
     | -------------------------------------------------------------------*/

    public function create(array $data): ?string
    {
        File::ensureDirectoryExists($this->postsPath);

        $date = $this->parseDate($data['date'] ?? null);
        $slug = $this->ensureUniqueSlug(Str::slug($data['title'] ?? 'untitled'));

        $filename = BlogPost::buildFilename($date, $slug);
        $path = $this->postsPath . '/' . $filename;

        $payload = $this->buildPayload($data, $date);

        if (File::put($path, BlogPost::toMarkdown($payload, $data['body'] ?? '')) === false) {
            return null;
        }

        $this->refreshBlog();
        return $slug;
    }

    public function update(string $currentSlug, array $data): ?string
    {
        $existing = $this->getPostBySlug($currentSlug);
        if (!$existing) {
            return null;
        }

        $date = $this->parseDate($data['date'] ?? null) ?? $existing->date;

        $newSlug = Str::slug($data['title'] ?? $existing->title);
        if ($newSlug === '') {
            $newSlug = $existing->slug;
        }

        $newFilename = BlogPost::buildFilename($date, $newSlug);
        $newPath = $this->postsPath . '/' . $newFilename;

        if ($newPath !== $existing->filepath && File::exists($newPath)) {
            $newSlug = $this->ensureUniqueSlug($newSlug, $existing->slug);
            $newFilename = BlogPost::buildFilename($date, $newSlug);
            $newPath = $this->postsPath . '/' . $newFilename;
        }

        $payload = $this->buildPayload($data, $date);
        $body = $data['body'] ?? $existing->rawBody();

        if (File::put($newPath, BlogPost::toMarkdown($payload, $body)) === false) {
            return null;
        }

        if ($newPath !== $existing->filepath && File::exists($existing->filepath)) {
            File::delete($existing->filepath);
        }

        if ($newSlug !== $existing->slug) {
            $this->moveImageDirectory($existing->slug, $newSlug);
        }

        $this->refreshBlog();
        return $newSlug;
    }

    public function delete(string $slug): bool
    {
        $post = $this->getPostBySlug($slug);
        if (!$post) {
            return false;
        }

        $deleted = File::delete($post->filepath);
        if ($deleted) {
            $imageDir = public_path('images/blog/' . $slug);
            if (File::isDirectory($imageDir)) {
                File::deleteDirectory($imageDir);
            }
            $this->refreshBlog();
        }
        return $deleted;
    }

    private function refreshBlog(): void
    {
        Artisan::call('blog:refresh');
    }

    private function parseDate(mixed $date): ?Carbon
    {
        if (!$date) {
            return Carbon::now();
        }
        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    private function ensureUniqueSlug(string $slug, ?string $ignoreSlug = null): string
    {
        $original = $slug ?: 'untitled';
        $candidate = $original;
        $i = 2;

        $existingSlugs = $this->getAllPosts()
            ->pluck('slug')
            ->reject(fn($s) => $ignoreSlug !== null && $s === $ignoreSlug)
            ->values();

        while ($existingSlugs->contains($candidate)) {
            $candidate = $original . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function buildPayload(array $data, Carbon $date): array
    {
        return [
            'title' => trim($data['title'] ?? ''),
            'subtitle' => trim($data['subtitle'] ?? ''),
            'summary' => trim($data['summary'] ?? ''),
            'categories' => is_array($data['categories'] ?? null) ? $data['categories'] : [],
            'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            'hero_image' => trim($data['hero_image'] ?? ''),
            'author' => trim($data['author'] ?? ''),
            'date' => $date->format('Y-m-d H:i:s'),
        ];
    }

    private function moveImageDirectory(string $oldSlug, string $newSlug): void
    {
        $oldDir = public_path('images/blog/' . $oldSlug);
        $newDir = public_path('images/blog/' . $newSlug);

        if (File::isDirectory($oldDir) && !File::isDirectory($newDir)) {
            File::moveDirectory($oldDir, $newDir);
        }
    }
}
