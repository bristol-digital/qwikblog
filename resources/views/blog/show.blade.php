@extends(config('qwikblog.layout', 'app'))
@section('title', $post->title)

@push('head')
    <meta name="description" content="{{ $post->summary }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @if($post->isScheduled())
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $post->summary }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @if($post->effectiveHeroImage())
        <meta property="og:image" content="{{ url($post->effectiveHeroImage()) }}">
    @endif

    <meta property="article:published_time" content="{{ $post->date->toIso8601String() }}">
    @if($post->author)
        <meta property="article:author" content="{{ $post->author }}">
    @endif
    @foreach(($post->categories ?? []) as $category)
        <meta property="article:section" content="{{ $category }}">
    @endforeach
    @foreach(($post->tags ?? []) as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->title }}">
    <meta name="twitter:description" content="{{ $post->summary }}">
    @if($post->effectiveHeroImage())
        <meta name="twitter:image" content="{{ url($post->effectiveHeroImage()) }}">
    @endif
@endpush

@section('content')

@php($displayImages = $post->displayImages())
@php($postCategories = $post->categories ?? [])
@php($postTags = $post->tags ?? [])

<section class="text-gray-600 body-font">
    <div class="container max-w-7xl px-5 pb-24 mx-auto flex flex-col">

        <h1 class="text-4xl font-thin my-5">{{ config('app.name') }}</h1>

        @if(count($displayImages) > 0)
            @php($paths = array_column($displayImages, 'path'))
            <section
                class="bg-gray-900 rounded-lg overflow-hidden"
                x-data="{
                    images: @js($paths),
                    currentIndex: 0,
                    next() { this.currentIndex = (this.currentIndex + 1) % this.images.length; },
                    prev() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length; },
                    goTo(index) { this.currentIndex = index; }
                }"
                x-on:keydown.left.window="prev()"
                x-on:keydown.right.window="next()"
            >
                <div class="relative h-80 md:h-[60vh] overflow-hidden">
                    @foreach($displayImages as $index => $image)
                        <div class="absolute inset-0 transition-opacity duration-500"
                             :class="currentIndex === {{ $index }} ? 'opacity-100' : 'opacity-0'">
                            <img src="{{ $image['path'] }}" alt=""
                                 class="w-full h-full object-cover blur-xl scale-110 opacity-40">
                        </div>
                    @endforeach

                    @foreach($displayImages as $index => $image)
                        <div class="absolute inset-0 flex items-center justify-center p-4 md:p-8 transition-opacity duration-500"
                             :class="currentIndex === {{ $index }} ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                            <img src="{{ $image['path'] }}" alt="{{ $post->title }}"
                                 class="max-h-full max-w-full object-contain rounded shadow-2xl">
                        </div>
                    @endforeach

                    @if(count($displayImages) > 1)
                        <button type="button" x-on:click="prev"
                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors z-10"
                                aria-label="Previous image">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button type="button" x-on:click="next"
                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors z-10"
                                aria-label="Next image">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                            @foreach($displayImages as $index => $image)
                                <button type="button" x-on:click="goTo({{ $index }})"
                                        class="w-3 h-3 rounded-full transition-colors"
                                        :class="currentIndex === {{ $index }} ? 'bg-white' : 'bg-white/40 hover:bg-white/60'"
                                        aria-label="Go to image {{ $index + 1 }}"></button>
                            @endforeach
                        </div>

                        <div class="absolute top-4 right-4 bg-black/50 text-white text-sm px-3 py-1 rounded-full z-10">
                            <span x-text="currentIndex + 1"></span> / {{ count($displayImages) }}
                        </div>
                    @endif
                </div>

                @if(count($displayImages) > 1)
                    <div class="bg-gray-800 py-4">
                        <div class="px-4">
                            <div class="flex gap-2 overflow-x-auto py-2 justify-center">
                                @foreach($displayImages as $index => $image)
                                    <button type="button" x-on:click="goTo({{ $index }})"
                                            class="flex-shrink-0 w-20 h-20 rounded overflow-hidden ring-2 transition-colors"
                                            :class="currentIndex === {{ $index }} ? 'ring-blue-400' : 'ring-transparent hover:ring-white/50'">
                                        <img src="{{ $image['path'] }}" alt="" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <div class="flex flex-col sm:flex-row mt-10">
            <div class="sm:w-2/3 sm:pr-8 sm:py-8">
                <div class="mb-8">
                    <a href="{{ route('blog.index') }}"
                       class="inline-flex items-center text-blue-900 hover:text-gray-800 mb-4 font-thin">
                        &laquo; Back to Blog
                    </a>

                    <div class="flex items-center justify-between -mb-5">
                        @if(count($postCategories) > 0)
                            <span class="text-sm font-thin text-gray-600 uppercase tracking-wide">
                                {{ implode(' • ', $postCategories) }}
                            </span>
                        @else
                            <span></span>
                        @endif
                        <time class="text-gray-500 font-thin text-right" datetime="{{ $post->date->format('Y-m-d') }}">
                            {{ $post->date->format('jS F Y') }}
                        </time>
                    </div>
                </div>

                <h1 class="font-thin text-5xl mb-3">{{ $post->title }}</h1>
                @if($post->subtitle)
                    <h2 class="font-thin text-3xl mb-3">{{ $post->subtitle }}</h2>
                @endif

                <p class="font-thin text-base text-gray-500 mb-6">
                    @if($post->author)
                        By
                        <a href="{{ route('blog.author', \Illuminate\Support\Str::slug($post->author)) }}"
                           class="font-medium text-gray-700 hover:text-blue-900 hover:underline">{{ $post->author }}</a>
                        <span class="mx-1">·</span>
                    @endif
                    {{ $post->readingTime() }} min read
                </p>

                <article class="prose prose-xl max-w-none font-thin
                                prose-headings:font-thin
                                prose-a:text-blue-900 prose-a:no-underline hover:prose-a:underline
                                prose-blockquote:border-blue-900 prose-blockquote:font-thin
                                prose-code:bg-gray-100 prose-code:rounded prose-code:px-1 prose-code:before:content-none prose-code:after:content-none">
                    {!! $post->content !!}
                </article>

                @if(count($relatedPosts) > 0)
                    <section class="mt-12 pt-8 border-t border-gray-200">
                        <h2 class="text-2xl font-thin mb-6">Related posts</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach($relatedPosts as $related)
                                <a href="{{ route('blog.show', $related->slug) }}"
                                   class="group block bg-white rounded border border-gray-200 hover:border-blue-800 hover:shadow-md transition overflow-hidden">
                                    @if($related->effectiveHeroImage())
                                        <div class="h-32 overflow-hidden bg-gray-100">
                                            <img src="{{ $related->effectiveHeroImage() }}" alt=""
                                                 class="w-full h-full object-cover">
                                        </div>
                                    @endif
                                    <div class="p-4">
                                        @if(count($related->categories) > 0)
                                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1 truncate">
                                                {{ implode(' • ', $related->categories) }}
                                            </p>
                                        @endif
                                        <h3 class="font-thin text-lg text-gray-900 group-hover:text-blue-900 mb-1 leading-tight">
                                            {{ $related->title }}
                                        </h3>
                                        <time class="text-xs text-gray-500" datetime="{{ $related->date->format('Y-m-d') }}">
                                            {{ $related->date->format('jS M Y') }}
                                        </time>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <nav class="mt-12 pt-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                        @if($previous)
                            <a href="{{ route('blog.show', $previous->slug) }}"
                               class="group p-6 bg-white rounded border border-gray-200 hover:border-blue-800 hover:shadow-md transition">
                                <span class="text-sm text-gray-500 block mb-2 font-thin">&larr; Previous</span>
                                <span class="text-lg font-thin text-gray-900 group-hover:text-blue-900">
                                    {{ $previous->title }}
                                </span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if($next)
                            <a href="{{ route('blog.show', $next->slug) }}"
                               class="group p-6 bg-white rounded border border-gray-200 hover:border-blue-800 hover:shadow-md transition text-right">
                                <span class="text-sm text-gray-500 block mb-2 font-thin">Next &rarr;</span>
                                <span class="text-lg font-thin text-gray-900 group-hover:text-blue-900">
                                    {{ $next->title }}
                                </span>
                            </a>
                        @endif
                    </div>
                </nav>
            </div>

            <div class="sm:w-1/3 sm:pl-8 sm:py-8 sm:border-l border-gray-200 sm:border-t-0 border-t mt-4 pt-4 sm:mt-0 text-center sm:text-left">
                <h2 class="font-light text-2xl text-right mb-5">Blog Posts</h2>

                <ul class="font-thin text-xl text-right">
                    @foreach($allPosts as $sidebarPost)
                        <li class="py-1">
                            <a class="hover:underline block @if($sidebarPost->slug == $post->slug) border-r-4 border-blue-400 bg-blue-50/20 pr-2 -mr-3 @endif"
                               href="{{ route('blog.show', $sidebarPost->slug) }}">
                                {{ $sidebarPost->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if(count($postCategories) > 0)
                    <h3 class="font-light text-xl text-right mt-8 mb-3">Categories</h3>
                    <div class="flex flex-wrap gap-2 justify-end">
                        @foreach($postCategories as $category)
                            <a href="{{ \BristolDigital\QwikBlog\Support\BlogUrls::category($category) }}"
                               class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200 transition-colors">
                                {{ $category }}
                                <span class="opacity-60">({{ $categoryCounts[$category] ?? 0 }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(count($postTags) > 0)
                    <h3 class="font-light text-xl text-right mt-6 mb-3">Tags</h3>
                    <div class="flex flex-wrap gap-2 justify-end">
                        @foreach($postTags as $tag)
                            <a href="{{ \BristolDigital\QwikBlog\Support\BlogUrls::tag($tag) }}"
                               class="text-xs bg-blue-50 text-blue-800 px-3 py-1 rounded hover:bg-blue-100 transition-colors">
                                #{{ $tag }}
                                <span class="opacity-60">({{ $tagCounts[$tag] ?? 0 }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</section>
@endsection
