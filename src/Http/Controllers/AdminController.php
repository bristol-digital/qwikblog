<?php

namespace BristolDigital\QwikBlog\Http\Controllers;

use BristolDigital\QwikBlog\Services\BlogService;
use BristolDigital\QwikBlog\ValueObjects\BlogPost;
use Illuminate\Http\Request;

class AdminController
{
    public function __construct(
        private BlogService $blogService
    ) {}

    /* ---------------------------------------------------------------------
     | Auth
     | -------------------------------------------------------------------*/

    public function loginForm()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.posts.index');
        }

        return view('qwikblog::admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $expectedUser = env('ADMIN_USERNAME');
        $expectedPass = env('ADMIN_PASSWORD');

        if (!$expectedUser || !$expectedPass) {
            return back()->withErrors([
                'credentials' => 'Admin credentials are not configured. Set ADMIN_USERNAME and ADMIN_PASSWORD in .env.'
            ]);
        }

        if (
            hash_equals((string) $expectedUser, (string) $request->username) &&
            hash_equals((string) $expectedPass, (string) $request->password)
        ) {
            $request->session()->regenerate();
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.posts.index');
        }

        return back()->withErrors(['credentials' => 'Invalid username or password']);
    }

    public function logout(Request $request)
    {
        session()->forget('admin_authenticated');
        $request->session()->regenerate();
        return redirect()->route('admin.login');
    }

    /* ---------------------------------------------------------------------
     | Posts CRUD — index is the Livewire component BristolDigital\QwikBlog\Livewire\Admin\PostsIndex.
     | -------------------------------------------------------------------*/

    public function create()
    {
        return view('qwikblog::admin.posts.form', [
            'post' => null,
            'allCategories' => $this->blogService->getAllCategories(),
            'allTags' => $this->blogService->getAllTags(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePost($request);
        $validated['categories'] = BlogPost::parseList($validated['categories'] ?? '');
        $validated['tags'] = BlogPost::parseList($validated['tags'] ?? '');

        $slug = $this->blogService->create($validated);

        if (!$slug) {
            return back()
                ->withErrors(['save' => 'Failed to save post'])
                ->withInput();
        }

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post created successfully');
    }

    public function edit(string $slug)
    {
        $post = $this->blogService->getPostBySlug($slug);

        if (!$post) {
            abort(404);
        }

        return view('qwikblog::admin.posts.form', [
            'post' => $post,
            'allCategories' => $this->blogService->getAllCategories(),
            'allTags' => $this->blogService->getAllTags(),
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $validated = $this->validatePost($request);
        $validated['categories'] = BlogPost::parseList($validated['categories'] ?? '');
        $validated['tags'] = BlogPost::parseList($validated['tags'] ?? '');

        $newSlug = $this->blogService->update($slug, $validated);

        if (!$newSlug) {
            return back()
                ->withErrors(['save' => 'Failed to update post'])
                ->withInput();
        }

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post updated successfully');
    }

    public function destroy(string $slug)
    {
        if (!$this->blogService->delete($slug)) {
            return back()->withErrors(['delete' => 'Failed to delete post']);
        }

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post deleted successfully');
    }

    /* ---------------------------------------------------------------------
     | Helpers
     | -------------------------------------------------------------------*/

    protected function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'summary' => 'required|string|max:1000',
            // categories and tags arrive as comma-separated strings from the
            // chip-input widget's hidden field. parseList() splits them into
            // arrays before they hit BlogService.
            'categories' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:500',
            'hero_image' => 'nullable|string|max:500',
            'author' => 'required|string|max:255',
            'date' => 'required|date',
            'body' => 'required|string',
        ]);
    }
}
