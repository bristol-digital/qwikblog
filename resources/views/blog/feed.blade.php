<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }}</title>
        <link>{{ url('/blog') }}</link>
        <description>Latest posts from {{ config('app.name') }}</description>
        <language>en</language>
        <lastBuildDate>{{ $posts->first()?->date->toRssString() ?? now()->toRssString() }}</lastBuildDate>
        <atom:link href="{{ url('/blog/feed.xml') }}" rel="self" type="application/rss+xml" />
        @foreach($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ url('/blog/' . $post->slug) }}</link>
                <description><![CDATA[{!! $post->summary !!}]]></description>
                <pubDate>{{ $post->date->toRssString() }}</pubDate>
                <guid isPermaLink="true">{{ url('/blog/' . $post->slug) }}</guid>
                @if($post->author)
                <dc:creator><![CDATA[{{ $post->author }}]]></dc:creator>
                @endif
                @foreach($post->categories as $category)
                <category><![CDATA[{{ $category }}]]></category>
                @endforeach
                <content:encoded><![CDATA[{!! $post->content !!}]]></content:encoded>
            </item>
        @endforeach
    </channel>
</rss>
