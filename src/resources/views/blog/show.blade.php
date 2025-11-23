@extends('app')
@section('title', 'Blog')
@section('content')

    <section class="text-gray-600 body-font">
        <div class="container max-w-7xl px-5 pb-24 mx-auto">

            <h1 class="text-4xl font-thin my-5">Your {{ config('app.name') }} Blog</h1>

            <div class="flex flex-wrap -m-4">

                @forelse($posts as $post)

                    <div class="p-4 sm:w-1/2 lg:w-1/3 bg">
                        <div class="
                        h-full border-2 bg-white border-gray-200 border-opacity-60 rounded-lg overflow-hidden
                        hover:ease-in-out hover:opacity-95 duration-300 hover:transition hover:scale-105
                        hover:cursor-pointer
                    "
                             x-data
                             @click="window.location.href = '/blog/{{ $post->slug }}'"
                        >

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

                                <div class="absolute inset-0 flex items-end">
                                    <div class="p-4">
                                        <h1 class="text-white text-2xl font-thin
                                           inline-block px-4 py-2 rounded-lg
                                           bg-black/50 backdrop-blur-sm"
                                        >
                                            {{ $post->title }}
                                        </h1>
                                    </div>
                                </div>

                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <h2 class="tracking-tight text-xs title-font font-thin text-gray-800 mb-1">
                                        {{ $post->category }}
                                    </h2>

                                    <div class="flex items-center space-x-4 tracking-tight text-xs title-font font-thin text-gray-800 mb-1">
                                        <time datetime="{{ $post->date->format('Y-m-d') }}">
                                            {{ $post->date->format('jS M Y') }}
                                        </time>
                                    </div>
                                </div>

                                <h1 class="font-thin text-2xl text-gray-900 mb-3">{{ $post->subtitle ?: $post->title }}</h1>

                                <p class="font-light text-gray-500">{{ $post->summary }}</p>

                                <div class="flex justify-end bottom-0 flex-wrap mt-5">
                                    <a href="/blog/{{ $post->slug }}" class="text-blue-900 font-thin text-lg hover:underline italic inline-flex items-center md:mb-2 lg:mb-0 hover:ease-in-out hover:opacity-95 duration-300 hover:transition hover:scale-105">
                                        Read more &raquo;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

            </div>

        </div>
    </section>

@endsection
