<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/blog') }}</loc>
        <lastmod>{{ $latestDate }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach($posts as $post)
    <url>
        <loc>{{ url('/blog/' . $post->slug) }}</loc>
        <lastmod>{{ $post->date->format('Y-m-d') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    @foreach($categories as $category)
    <url>
        <loc>{{ \BristolDigital\QwikBlog\Support\BlogUrls::category($category) }}</loc>
        <lastmod>{{ $latestDate }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
    @endforeach
    @foreach($tags as $tag)
    <url>
        <loc>{{ \BristolDigital\QwikBlog\Support\BlogUrls::tag($tag) }}</loc>
        <lastmod>{{ $latestDate }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
    @endforeach
    @foreach($authors as $author)
    <url>
        <loc>{{ route('blog.author', \Illuminate\Support\Str::slug($author)) }}</loc>
        <lastmod>{{ $latestDate }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.4</priority>
    </url>
    @endforeach
    @foreach($archive as $year => $months)
    <url>
        <loc>{{ route('blog.archive.year', $year) }}</loc>
        <lastmod>{{ $latestDate }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
        @foreach($months as $month => $count)
        <url>
            <loc>{{ route('blog.archive.month', [$year, sprintf('%02d', $month)]) }}</loc>
            <lastmod>{{ $latestDate }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.3</priority>
        </url>
        @endforeach
    @endforeach
</urlset>
