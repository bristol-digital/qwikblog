<?php

/*
|--------------------------------------------------------------------------
| QwikBlog configuration
|--------------------------------------------------------------------------
| Every value can be overridden via environment variable. Defaults are
| sensible for a typical blog — only set what you actually want to change.
*/

return [

    'posts_path' => env('QWIKBLOG_POSTS_PATH'),

    'cache_duration' => (int) env('QWIKBLOG_CACHE_DURATION', 3600),

    'language' => env('QWIKBLOG_LANGUAGE'),

    'reading_wpm' => (int) env('QWIKBLOG_READING_WPM', 200),

    /*
    | Per-page count on the public blog index, search results, category /
    | tag / author / archive filters. 12 is a sensible default for grid
    | layouts (3 columns × 4 rows).
    */
    'per_page' => (int) env('QWIKBLOG_PER_PAGE', 12),

    /*
    | Per-page count on the admin posts table. Admin tables prefer dense
    | listings — 30 keeps the table compact while letting editors see
    | most of their pipeline without paging. Bump higher if your editors
    | regularly schedule far in advance and want everything in one view.
    */
    'admin_per_page' => (int) env('QWIKBLOG_ADMIN_PER_PAGE', 30),

    'feed_limit' => (int) env('QWIKBLOG_FEED_LIMIT', 50),

    'admin_path' => env('QWIKBLOG_ADMIN_PATH', 'admin'),

    /*
    | Middleware that protects the admin routes (post create/edit/delete,
    | image gallery, the Livewire posts table). Defaults to 'admin' — the
    | alias the package registers for its own AdminAuth middleware, which
    | uses ADMIN_USERNAME / ADMIN_PASSWORD from .env.
    |
    | To integrate with the host app's existing authentication instead of
    | maintaining a separate username/password, set this to 'auth' (or
    | whatever middleware your host uses to gate its own admin area). Then
    | logged-in host users can access the blog admin without a second login.
    |
    | Examples:
    |   QWIKBLOG_ADMIN_MIDDLEWARE=admin           // default — self-contained
    |   QWIKBLOG_ADMIN_MIDDLEWARE=auth            // Laravel's session auth
    |   QWIKBLOG_ADMIN_MIDDLEWARE=auth,can:edit-posts  // Laravel + a gate
    */
    'admin_middleware' => env('QWIKBLOG_ADMIN_MIDDLEWARE', 'admin'),

    /*
    | Route name that the admin layout's "Logout" button posts to. Defaults
    | to the package's own admin.logout route. If you've set
    | QWIKBLOG_ADMIN_MIDDLEWARE to integrate with Laravel's auth, set this
    | to 'logout' so the button calls Laravel's logout endpoint instead
    | (which is what's expected by users logged in via Laravel's auth flow).
    */
    'admin_logout_route' => env('QWIKBLOG_ADMIN_LOGOUT_ROUTE', 'admin.logout'),

    /*
    | Name of the host app's Blade layout that the public-facing blog views
    | should @extends. Defaults to 'app' (i.e. resources/views/app.blade.php).
    |
    | If your host app's main layout lives elsewhere — say, 'layouts.app' or
    | 'site.master' — set QWIKBLOG_LAYOUT in .env or override here.
    |
    | If your host doesn't have a layout at all, set this to
    | 'qwikblog::layouts.app' to fall back to the package's bundled
    | minimal layout (CDN Tailwind, Typography, Alpine — works zero-setup).
    |
    | Whichever layout is used, it must have @stack('head') somewhere in
    | its <head> for SEO meta injection to work, and @yield('content')
    | for the blog content to render.
    */
    'layout' => env('QWIKBLOG_LAYOUT', 'app'),

    'taxonomy_url_style' => env('QWIKBLOG_TAXONOMY_URL_STYLE', 'flat'),

    'related' => [
        'tag_weight' => (int) env('QWIKBLOG_RELATED_TAG_WEIGHT', 2),
        'category_weight' => (int) env('QWIKBLOG_RELATED_CATEGORY_WEIGHT', 1),
        'limit' => (int) env('QWIKBLOG_RELATED_LIMIT', 3),
    ],

];
