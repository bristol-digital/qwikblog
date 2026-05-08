<?php

namespace BristolDigital\QwikBlog;

use BristolDigital\QwikBlog\Console\Commands\ImportPosts;
use BristolDigital\QwikBlog\Console\Commands\InstallExamples;
use BristolDigital\QwikBlog\Console\Commands\RefreshBlog;
use BristolDigital\QwikBlog\Http\Middleware\AdminAuth;
use BristolDigital\QwikBlog\Livewire\Admin\BlogImages;
use BristolDigital\QwikBlog\Livewire\Admin\PostsIndex;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * QwikBlog package service provider.
 *
 * Wires the package into a host Laravel app: registers routes, views,
 * Blade components, Livewire components, the admin auth middleware,
 * Artisan commands, and publishable resources (config, views, seeds).
 *
 * Auto-discovered via composer.json's `extra.laravel.providers` — host
 * apps don't need to manually register this.
 */
class QwikBlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Make the package's default config available, merged with any
        // config the host has published to config/qwikblog.php.
        $this->mergeConfigFrom(__DIR__.'/../config/qwikblog.php', 'qwikblog');
    }

    public function boot(Router $router): void
    {
        // Routes — public blog + admin, all defined in routes/web.php.
        // Wrap in the 'web' middleware group so package routes get session,
        // CSRF, the $errors variable share, and cookie encryption — without
        // this, package routes that depend on session-bound machinery (login
        // forms, validation error display, the admin's auth flow) break with
        // "Undefined variable $errors" or session-related failures.
        Route::middleware('web')->group(__DIR__.'/../routes/web.php');

        // Views — accessible as qwikblog::blog.show, qwikblog::admin.login, etc.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qwikblog');

        // Anonymous Blade components — admin.layout and admin.chip-input
        // become <x-qwikblog::admin.layout> and <x-qwikblog::admin.chip-input>.
        Blade::anonymousComponentNamespace('components', 'qwikblog');

        // Admin auth middleware alias — used in routes/web.php as middleware('admin').
        $router->aliasMiddleware('admin', AdminAuth::class);

        // Livewire components — registered with explicit names so that
        // <livewire:admin.posts-index /> and <livewire:admin.blog-images />
        // resolve correctly inside the package's view namespace.
        Livewire::component('admin.posts-index', PostsIndex::class);
        Livewire::component('admin.blog-images', BlogImages::class);

        if ($this->app->runningInConsole()) {
            // Artisan commands.
            $this->commands([
                RefreshBlog::class,
                ImportPosts::class,
                InstallExamples::class,
            ]);

            // Publishable assets — host apps run vendor:publish to copy
            // these into their own project where they can be edited.
            $this->publishes([
                __DIR__.'/../config/qwikblog.php' => config_path('qwikblog.php'),
            ], 'qwikblog-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/qwikblog'),
            ], 'qwikblog-views');

            $this->publishes([
                __DIR__.'/../resources/seeds' => resource_path('seeds'),
            ], 'qwikblog-seeds');

            $this->publishes([
                __DIR__.'/../resources/js/admin.js' => resource_path('js/qwikblog-admin.js'),
            ], 'qwikblog-admin-js');
        }
    }
}
