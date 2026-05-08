<?php

namespace BristolDigital\QwikBlog\Http\Controllers;

use BristolDigital\QwikBlog\Services\BlogService;
use BristolDigital\QwikBlog\ValueObjects\BlogPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController
{
    public function __construct(
        private BlogService $blogService
    ) {}

    public function index(): View
    {
        return $this->renderIndex();
    }

    public function show(string $slug): View
    {
        $isAdmin = session('admin_authenticated');
        $post = $isAdmin
            ? $this->blogService->getPostBySlug($slug)
            : $this->blogService->getPublishedPostBySlug($slug);

        if ($post) {
            return $this->renderPost($post);
        }

        if (config('qwikblog.taxonomy_url_style', 'flat') !== 'prefixed') {
            foreach ($this->blogService->getPublishedCategories() as $category) {
                if (Str::slug($category) === $slug) {
                    return $this->renderIndex(category: $category);
                }
            }
            foreach ($this->blogService->getPublishedTags() as $tag) {
                if (Str::slug($tag) === $slug) {
                    return $this->renderIndex(tag: $tag);
                }
            }
        }

        abort(404);
    }

    public function category(string $slug): View
    {
        foreach ($this->blogService->getPublishedCategories() as $category) {
            if (Str::slug($category) === $slug) {
                return $this->renderIndex(category: $category);
            }
        }
        abort(404);
    }

    public function tag(string $slug): View
    {
        foreach ($this->blogService->getPublishedTags() as $tag) {
            if (Str::slug($tag) === $slug) {
                return $this->renderIndex(tag: $tag);
            }
        }
        abort(404);
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $posts = $query !== ''
            ? $this->blogService->searchPublishedPosts($query)
            : collect();

        return $this->buildIndexView($posts, searchQuery: $query);
    }

    public function author(string $slug): View
    {
        foreach ($this->blogService->getPublishedAuthors() as $author) {
            if (Str::slug($author) === $slug) {
                $posts = $this->blogService->getPublishedPosts()
                    ->filter(fn(BlogPost $p) => $p->author === $author)
                    ->values();

                return $this->buildIndexView($posts, activeAuthor: $author);
            }
        }
        abort(404);
    }

    public function archiveYear(int $year): View
    {
        $posts = $this->blogService->getPostsByDate($year);
        if ($posts->isEmpty()) {
            abort(404);
        }

        return $this->buildIndexView(
            $posts,
            activeArchive: ['year' => $year, 'month' => null, 'label' => (string) $year],
        );
    }

    public function archiveMonth(int $year, int $month): View
    {
        $posts = $this->blogService->getPostsByDate($year, $month);
        if ($posts->isEmpty()) {
            abort(404);
        }

        $label = Carbon::create($year, $month, 1)->format('F Y');

        return $this->buildIndexView(
            $posts,
            activeArchive: ['year' => $year, 'month' => $month, 'label' => $label],
        );
    }

    public function feed(): Response
    {
        $limit = (int) config('qwikblog.feed_limit', 50);
        $posts = $this->blogService->getPublishedPosts()->take($limit);

        return response()
            ->view('qwikblog::blog.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $posts = $this->blogService->getPublishedPosts();
        $categories = $this->blogService->getPublishedCategories();
        $tags = $this->blogService->getPublishedTags();
        $authors = $this->blogService->getPublishedAuthors();
        $archive = $this->blogService->getPublishedArchive();

        $latestDate = $posts->first()?->date->format('Y-m-d') ?? now()->format('Y-m-d');

        return response()
            ->view('qwikblog::blog.sitemap', [
                'posts' => $posts,
                'categories' => $categories,
                'tags' => $tags,
                'authors' => $authors,
                'archive' => $archive,
                'latestDate' => $latestDate,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function renderPost(BlogPost $post): View
    {
        $adjacent = $this->blogService->getAdjacentPublishedPosts($post);

        return view('qwikblog::blog.show', [
            'post' => $post,
            'previous' => $adjacent['previous'],
            'next' => $adjacent['next'],
            'allPosts' => $this->blogService->getPublishedPosts(),
            'categoryCounts' => $this->blogService->getPublishedCategoryCounts(),
            'tagCounts' => $this->blogService->getPublishedTagCounts(),
            'relatedPosts' => $this->blogService->getRelatedPosts($post),
        ]);
    }

    private function renderIndex(?string $category = null, ?string $tag = null): View
    {
        $posts = $this->blogService->getPublishedPosts();

        if ($category) {
            $posts = $posts->filter(fn(BlogPost $p) => in_array($category, $p->categories, true));
        }
        if ($tag) {
            $posts = $posts->filter(fn(BlogPost $p) => in_array($tag, $p->tags, true));
        }

        return $this->buildIndexView(
            $posts->values(),
            activeCategory: $category,
            activeTag: $tag,
        );
    }

    /**
     * @param  array{year:int,month:?int,label:string}|null  $activeArchive
     */
    private function buildIndexView(
        Collection $posts,
        ?string $activeCategory = null,
        ?string $activeTag = null,
        ?string $activeAuthor = null,
        ?string $searchQuery = null,
        ?array $activeArchive = null,
    ): View {
        $perPage = (int) config('qwikblog.per_page', 12);

        return view('qwikblog::blog.index', [
            'posts' => $this->paginate($posts, $perPage),
            'allCategories' => $this->blogService->getPublishedCategoryCounts(),
            'allTags' => $this->blogService->getPublishedTagCounts(),
            'archive' => $this->blogService->getPublishedArchive(),
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
            'activeAuthor' => $activeAuthor,
            'activeArchive' => $activeArchive,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Build a LengthAwarePaginator from a Collection. Doesn't rely on the
     * Collection::paginate() macro that some Laravel apps register in their
     * AppServiceProvider — the package shouldn't depend on host-app
     * configuration for pagination to work.
     *
     * The 'query' option preserves any other URL params (?q=… for search,
     * etc.) on the page links automatically — no manual appends() needed.
     */
    private function paginate(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }
}
