@extends(config('qwikblog.layout', 'layouts.app'))
@section('title', $post->title)
@section('content')

    <section class="text-gray-600 body-font">
        <div class="container max-w-7xl px-5 pb-24 mx-auto flex flex-col">
            <div class="flex flex-wrap -m-4">
                <div class="p-4">

                    <h1 class="text-4xl font-thin my-5">Your {{ config('app.name') }} Blog</h1>

                    @if($post->heroImage)
                        <div class="relative rounded-lg h-64 overflow-hidden">

                            <div class="absolute inset-0">
                                <img
                                        src="{{ $post->heroImage }}"
                                        class="w-full h-full object-cover blur-sm opacity-60"
                                >
                            </div>

                            <div class="absolute inset-0 flex items-center justify-center">
                                <img
                                        src="{{ $post->heroImage }}"
                                        class="h-full object-contain"
                                >
                            </div>

                            <div class="absolute inset-0 flex items-end md:items-center">
                                <div class="p-4">
                                    <h1 class="text-white text-3xl md:text-4xl font-thin
                           inline-block px-4 py-2 rounded-lg
                           bg-black/50 backdrop-blur-sm"
                                    >
                                        {{ $post->title }}
                                    </h1>
                                </div>
                            </div>

                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row mt-10">
                        {{-- Main Content (2/3) --}}
                        <div class="sm:w-2/3 sm:pr-8 sm:py-8">
                            {{-- Post Header --}}
                            <div class="mb-8">
                                <a href="{{ route('blog.index') }}"
                                   class="inline-flex items-center text-blue-900 hover:text-gray-800 mb-4 font-thin">
                                    &laquo; Back to Blog
                                </a>

                                <div class="flex items-center justify-between -mb-5">
                        <span class="text-sm font-thin text-gray-600 uppercase tracking-wide">
                            {{ $post->category }}
                        </span>
                                    <time class="text-gray-500 font-thin text-right" datetime="{{ $post->date->format('Y-m-d') }}">
                                        {{ $post->date->format('jS F Y') }}
                                    </time>
                                </div>

                            </div>

                            <h1 class="font-thin text-5xl mb-3">{{ $post->title }}</h1>
                            <h2 class="font-thin text-3xl mb-3">{{ $post->subtitle }}</h2>

                            <article class="font-thin text-2xl leading-snug [&>p]:mb-4">
                                {!! $post->content !!}
                            </article>

                            {{-- Previous/Next Navigation --}}
                            <nav class="mt-12 pt-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                                    @if($previous)
                                        <a href="{{ route('blog.show', $previous->slug) }}"
                                           class="group p-6 bg-white rounded border border-gray-200 hover:border-blue-800 hover:shadow-md transition">
                                            <span class="text-sm text-gray-500 block mb-2 font-thin">← Previous</span>
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
                                            <span class="text-sm text-gray-500 block mb-2 font-thin">Next →</span>
                                            <span class="text-lg font-thin text-gray-900 group-hover:text-blue-900">
                                {{ $next->title }}
                            </span>
                                        </a>
                                    @endif
                                </div>
                            </nav>
                        </div>

                        {{-- Sidebar (1/3) --}}
                        <div class="sm:w-1/3 sm:pl-8 sm:py-8 sm:border-l border-gray-200 sm:border-t-0 border-t mt-4 pt-4 sm:mt-0 text-center sm:text-left">
                            <h2 class="font-light text-2xl text-right mb-5">Blog Posts</h2>

                            <ul class="font-thin text-xl text-right">
                                @foreach($allPosts as $sidebarPost)
                                    <li class="py-1">
                                        <a
                                                class="hover:underline block @if($sidebarPost->slug == $post->slug) border-r-4 border-blue-400 bg-blue-50/20 pr-2 -mr-3 @endif "
                                                href="{{ route('blog.show', $sidebarPost->slug) }}">
                                            {{ $sidebarPost->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                    </div>


                </div>
            </div>
        </div>
    </section>
@endsection
