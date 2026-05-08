<?php

namespace BristolDigital\QwikBlog\Support;

use Illuminate\Support\Str;

/**
 * Centralises the choice between flat (/blog/palos) and prefixed
 * (/blog/category/palos) taxonomy URLs.
 *
 * Both route names always exist — see routes/web.php — so calling either
 * form's route() helper directly works regardless of config. This helper
 * is what views should use to get the *canonical* URL form for the active
 * config setting.
 *
 * Usage in Blade:
 *   <a href="{{ \BristolDigital\QwikBlog\Support\BlogUrls::category($category) }}">{{ $category }}</a>
 */
class BlogUrls
{
    public static function category(string $category): string
    {
        $slug = Str::slug($category);

        return self::isPrefixed()
            ? route('blog.category', $slug)
            : route('blog.show', $slug);
    }

    public static function tag(string $tag): string
    {
        $slug = Str::slug($tag);

        return self::isPrefixed()
            ? route('blog.tag', $slug)
            : route('blog.show', $slug);
    }

    public static function isPrefixed(): bool
    {
        return config('qwikblog.taxonomy_url_style', 'flat') === 'prefixed';
    }
}
