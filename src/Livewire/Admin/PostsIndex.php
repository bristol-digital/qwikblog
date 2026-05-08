<?php

namespace BristolDigital\QwikBlog\Livewire\Admin;

use BristolDigital\QwikBlog\Services\BlogService;
use BristolDigital\QwikBlog\ValueObjects\BlogPost;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin posts index — full-page Livewire component.
 *
 * Features:
 *  - Free-text search across title, subtitle, summary and author
 *  - Filter dropdowns for category, tag and publication status
 *  - Pagination (default 30/page, configurable via qwikblog.admin_per_page)
 *  - URL-persisted filter state — refreshing or sharing a URL keeps filters
 *  - wire:poll on the parent so scheduled-post countdowns and any
 *    out-of-band file edits show up within 30 seconds
 *
 * The status filter is the headline feature for editors scheduling far in
 * advance — a "Scheduled" view shows just the pipeline without the noise
 * of everything that's already published.
 */
class PostsIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $tag = '';

    /** 'published' | 'scheduled' | '' (= all) */
    #[Url(except: '')]
    public string $status = '';

    /*
     * Any filter change resets pagination — page 5 of "all posts" doesn't
     * mean the same thing once you've narrowed to category=Palos.
     */
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedTag(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'tag', 'status']);
        $this->resetPage();
    }

    public function delete(string $slug, BlogService $blogService): void
    {
        $blogService->delete($slug);
    }

    public function render(BlogService $blogService)
    {
        // Admin scope — getAllPosts() includes scheduled posts.
        $allPosts = $blogService->getAllPosts();

        $filtered = $allPosts;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $filtered = $filtered->filter(function (BlogPost $p) use ($needle) {
                return str_contains(mb_strtolower($p->title), $needle)
                    || str_contains(mb_strtolower($p->subtitle), $needle)
                    || str_contains(mb_strtolower($p->summary), $needle)
                    || str_contains(mb_strtolower($p->author), $needle);
            });
        }

        if ($this->category !== '') {
            $filtered = $filtered->filter(
                fn(BlogPost $p) => in_array($this->category, $p->categories, true)
            );
        }

        if ($this->tag !== '') {
            $filtered = $filtered->filter(
                fn(BlogPost $p) => in_array($this->tag, $p->tags, true)
            );
        }

        if ($this->status === 'published') {
            $filtered = $filtered->filter(fn(BlogPost $p) => $p->isPublished());
        } elseif ($this->status === 'scheduled') {
            $filtered = $filtered->filter(fn(BlogPost $p) => $p->isScheduled());
        }

        $perPage = (int) config('qwikblog.admin_per_page', 30);
        $page = (int) ($this->getPage() ?: 1);

        // Manual paginator (no Collection::paginate macro dependency).
        $paginator = new LengthAwarePaginator(
            $filtered->values()->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('qwikblog::livewire.admin.posts-index', [
            'posts' => $paginator,
            'totalCount' => $allPosts->count(),
            'filteredCount' => $filtered->count(),
            'publishedCount' => $allPosts->filter(fn(BlogPost $p) => $p->isPublished())->count(),
            'scheduledCount' => $allPosts->filter(fn(BlogPost $p) => $p->isScheduled())->count(),
            'allCategories' => $blogService->getAllCategories(),
            'allTags' => $blogService->getAllTags(),
            'hasFilters' => $this->search !== '' || $this->category !== '' || $this->tag !== '' || $this->status !== '',
        ])->layout('qwikblog::components.admin.layout');
    }
}
