<?php

namespace BristolDigital\QwikBlog\Services;  // ← Changed namespace

use BristolDigital\QwikBlog\ValueObjects\BlogPost;  // ← Changed import
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogService
{
    private string $postsPath;
    private int $cacheDuration;

    public function __construct()
    {
        $this->postsPath = config('qwikblog.posts_path', resource_path('posts'));
        $this->cacheDuration = config('qwikblog.cache_duration', 3600);
    }

    public function getAllPosts(): Collection
    {
        return Cache::remember('qwikblog.all_posts', $this->cacheDuration, function () {
            if (!File::exists($this->postsPath)) {
                return collect();
            }

            $files = File::glob($this->postsPath . '/*.md');

            return collect($files)
                ->map(fn($file) => BlogPost::fromFile($file))
                ->sortByDesc('date')
                ->values();
        });
    }

    public function getPaginatedPosts(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('qwikblog.per_page', 12);
        $posts = $this->getAllPosts();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $posts->slice($offset, $perPage)->values(),
            $posts->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function getPostBySlug(string $slug): ?BlogPost
    {
        return $this->getAllPosts()->firstWhere('slug', $slug);
    }

    public function getAdjacentPosts(BlogPost $currentPost): array
    {
        $posts = $this->getAllPosts();
        $currentIndex = $posts->search(fn($post) => $post->slug === $currentPost->slug);

        return [
            'previous' => $posts->get($currentIndex - 1),
            'next' => $posts->get($currentIndex + 1),
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('qwikblog.all_posts');
    }
}
