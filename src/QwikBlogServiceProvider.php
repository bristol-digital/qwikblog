<?php

namespace YourName\QwikBlog;

use Illuminate\Support\ServiceProvider;
use YourName\QwikBlog\Services\BlogService;

class QwikBlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/qwikblog.php', 'qwikblog'
        );

        $this->app->singleton(BlogService::class, function ($app) {
            return new BlogService();
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/qwikblog.php' => config_path('qwikblog.php'),
        ], 'qwikblog-config');

        // Publish views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/qwikblog'),
        ], 'qwikblog-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'qwikblog');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
