<?php

namespace BristolDigital\QwikBlog\Http\Controllers;  // ← Changed namespace

use Illuminate\Routing\Controller;  // ← Use base Controller
use BristolDigital\QwikBlog\Services\BlogService;  // ← Changed import
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private BlogService $blogService
    ) {}

    public function index(): View
    {
        $perPage = config('qwikblog.per_page', 12);

        return view('qwikblog::blog.index', [  // ← Changed view namespace
            'posts' => $this->blogService->getPaginatedPosts($perPage),
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->blogService->getPostBySlug($slug);

        abort_if(!$post, 404);

        $adjacent = $this->blogService->getAdjacentPosts($post);
        $allPosts = $this->blogService->getAllPosts();

        return view('qwikblog::blog.show', [  // ← Changed view namespace
            'post' => $post,
            'previous' => $adjacent['previous'],
            'next' => $adjacent['next'],
            'allPosts' => $allPosts,
        ]);
    }
}
