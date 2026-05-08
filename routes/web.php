<?php

use BristolDigital\QwikBlog\Http\Controllers\AdminController;
use BristolDigital\QwikBlog\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home — the blog
|--------------------------------------------------------------------------
| The blog is the site. / serves the blog index directly (no redirect).
| /blog still works as well — both call the same controller method, both
| are valid URLs. The canonical link tag in the index view points to the
| URL the user is actually visiting, so there's no SEO duplicate-content
| problem in practice.
|
| If you later add other pages to the host site and want / to be something
| other than the blog, remove this route.
*/
Route::get('/', [BlogController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [BlogController::class, 'sitemap'])->name('sitemap');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');

    Route::get('/feed.xml', [BlogController::class, 'feed'])->name('feed');
    Route::get('/search', [BlogController::class, 'search'])->name('search');
    Route::get('/author/{slug}', [BlogController::class, 'author'])->name('author');

    Route::get('/category/{slug}', [BlogController::class, 'category'])->name('category');
    Route::get('/tag/{slug}', [BlogController::class, 'tag'])->name('tag');

    Route::get('/{year}/{month}', [BlogController::class, 'archiveMonth'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}'])
        ->name('archive.month');

    Route::get('/{year}', [BlogController::class, 'archiveYear'])
        ->where('year', '[0-9]{4}')
        ->name('archive.year');

    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

$adminPath = config('qwikblog.admin_path', 'admin');

Route::prefix($adminPath)->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'login']);
    Route::post('logout', [AdminController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.posts.index'));

        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', \BristolDigital\QwikBlog\Livewire\Admin\PostsIndex::class)->name('index');

            Route::get('create', [AdminController::class, 'create'])->name('create');
            Route::post('/', [AdminController::class, 'store'])->name('store');
            Route::get('{slug}/edit', [AdminController::class, 'edit'])->name('edit');
            Route::put('{slug}', [AdminController::class, 'update'])->name('update');
            Route::delete('{slug}', [AdminController::class, 'destroy'])->name('destroy');
            Route::get('{slug}/images', \BristolDigital\QwikBlog\Livewire\Admin\BlogImages::class)->name('images');
        });
    });
});
