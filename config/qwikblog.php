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

    'taxonomy_url_style' => env('QWIKBLOG_TAXONOMY_URL_STYLE', 'flat'),

    'related' => [
        'tag_weight' => (int) env('QWIKBLOG_RELATED_TAG_WEIGHT', 2),
        'category_weight' => (int) env('QWIKBLOG_RELATED_CATEGORY_WEIGHT', 1),
        'limit' => (int) env('QWIKBLOG_RELATED_LIMIT', 3),
    ],

];
