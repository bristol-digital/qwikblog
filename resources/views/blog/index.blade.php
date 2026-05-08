@extends(config('qwikblog.layout', 'app'))
@section('title', $searchQuery
    ? "Search: {$searchQuery}"
    : ($activeCategory
        ? "Posts in {$activeCategory}"
        : ($activeTag
            ? "Posts tagged #{$activeTag}"
            : ($activeAuthor
                ? "Posts by {$activeAuthor}"
                : (!empty($activeArchive)
                    ? "Archive: {$activeArchive['label']}"
                    : 'Blog')))))

@if(!empty($searchQuery))
    @push('head')
        <meta name="robots" content="noindex">
    @endpush
@endif

@section('content')

@php($archiveTotalMonths = collect($archive)->sum(fn($months) => count($months)))

<section class="text-gray-600 body-font">
    <div class="container max-w-7xl px-5 pb-24 mx-auto">

        <div class="flex flex-wrap items-baseline gap-4 my-5">
            <h1 class="text-4xl font-thin">
                @if(!empty($searchQuery))
                    Search results for <span class="text-blue-900">"{{ $searchQuery }}"</span>
                @elseif($activeCategory)
                    Posts in <span class="text-blue-900">{{ $activeCategory }}</span>
                @elseif($activeTag)
                    Posts tagged <span class="text-blue-900">#{{ $activeTag }}</span>
                @elseif(!empty($activeAuthor))
                    Posts by <span class="text-blue-900">{{ $activeAuthor }}</span>
                @elseif(!empty($activeArchive))
                    Archive: <span class="text-blue-900">{{ $activeArchive['label'] }}</span>
                @else
                    {{ config('app.name') }}
                @endif
            </h1>

            @if(!empty($searchQuery) || $activeCategory || $activeTag || !empty($activeAuthor) || !empty($activeArchive))
                <a href="{{ route('blog.index') }}" class="text-sm text-blue-900 hover:underline">All posts</a>
            @endif
        </div>

        {{-- Outer 5-column grid on desktop: posts span 4, sidebar takes 1
             (i.e. sidebar = 20% of width, narrower than each post column).
             Single column on mobile. --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            {{-- ============================ POSTS (4 of 5 cols) ============================ --}}
            <div class="lg:col-span-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                    @forelse($posts as $post)
                        @php($heroImage = $post->effectiveHeroImage())
                        @php($postCategories = $post->categories ?? [])
                        @php($postTags = $post->tags ?? [])

                        <div class="
                            h-full border-2 bg-white border-gray-200 border-opacity-60 rounded-lg overflow-hidden
                            hover:ease-in-out hover:opacity-95 duration-300 hover:transition hover:scale-105
                            hover:cursor-pointer
                        "
                             x-data
                             @click="window.location.href = '{{ route('blog.show', $post->slug) }}'"
                        >

                            @if($heroImage)
                                <div class="relative rounded-lg h-56 overflow-hidden">
                                    <div class="absolute inset-0">
                                        <img src="{{ $heroImage }}" class="w-full h-full object-cover blur-sm opacity-60">
                                    </div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <img src="{{ $heroImage }}" class="h-full object-contain">
                                    </div>
                                    <div class="absolute inset-0 flex items-end">
                                        <div class="p-3">
                                            <h1 class="text-white text-lg font-thin inline-block px-3 py-1.5 rounded-lg bg-black/50 backdrop-blur-sm">
                                                {{ $post->title }}
                                            </h1>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="p-5">
                                <div class="flex items-center justify-between gap-2">
                                    @if(count($postCategories) > 0)
                                        <h2 class="tracking-tight text-xs title-font font-thin text-gray-800 mb-1 truncate">
                                            {{ implode(' • ', $postCategories) }}
                                        </h2>
                                    @else
                                        <span></span>
                                    @endif

                                    <div class="flex items-center tracking-tight text-xs title-font font-thin text-gray-800 mb-1 whitespace-nowrap">
                                        <time datetime="{{ $post->date->format('Y-m-d') }}">
                                            {{ $post->date->format('jS M Y') }}
                                        </time>
                                    </div>
                                </div>

                                <h1 class="font-thin text-xl text-gray-900 mb-3 leading-tight">{{ $post->subtitle ?: $post->title }}</h1>

                                <p class="font-light text-sm text-gray-500">{{ $post->summary }}</p>

                                @if(count($postTags) > 0)
                                    <div class="flex flex-wrap gap-1 mt-3">
                                        @foreach($postTags as $tag)
                                            <span class="text-xs bg-blue-50 text-blue-800 px-2 py-0.5 rounded">#{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($post->author)
                                    <p class="text-xs text-gray-500 mt-3">By {{ $post->author }}</p>
                                @endif

                                <div class="flex justify-end mt-4">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="text-blue-900 font-thin hover:underline italic inline-flex items-center hover:ease-in-out hover:opacity-95 duration-300 hover:transition hover:scale-105">
                                        Read more &raquo;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-500 font-thin py-12">
                            @if(!empty($searchQuery))
                                No posts found for "{{ $searchQuery }}".
                                <a href="{{ route('blog.index') }}" class="text-blue-900 hover:underline">Show all posts</a>
                            @elseif($activeCategory || $activeTag || !empty($activeAuthor) || !empty($activeArchive))
                                No posts match this filter.
                                <a href="{{ route('blog.index') }}" class="text-blue-900 hover:underline">Show all posts</a>
                            @else
                                No posts yet.
                            @endif
                        </div>
                    @endforelse

                </div>

                @if($posts->hasPages())
                    <div class="mt-8">
                        {{ $posts->links() }}
                    </div>
                @endif

            </div>

            {{-- ============================ SIDEBAR (1 of 5 cols) ============================ --}}
            <aside class="lg:col-span-1 lg:sticky lg:top-4 lg:self-start space-y-4">

                {{-- Categories card. --}}
                @if(count($allCategories) > 0)
                    <div class="bg-gray-100 rounded-lg p-4">
                        <nav class="space-y-1.5">
                            @foreach($allCategories as $category => $count)
                                @php($isActive = $activeCategory === $category)
                                <a href="{{ \BristolDigital\QwikBlog\Support\BlogUrls::category($category) }}"
                                   class="block font-thin text-lg leading-tight {{ $isActive ? 'text-blue-900 font-medium' : 'text-gray-800 hover:text-blue-900' }}">
                                    {{ $category }}
                                    <span class="text-xs text-gray-500 ml-0.5">({{ $count }})</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                @endif

                {{-- Archive card. --}}
                @if($archiveTotalMonths >= 2)
                    <div class="bg-gray-100 rounded-lg p-4">
                        <nav class="space-y-2">
                            @foreach($archive as $year => $months)
                                @php($yearTotal = array_sum($months))
                                @php($isYearActive = !empty($activeArchive) && $activeArchive['year'] === $year && $activeArchive['month'] === null)
                                <div>
                                    <a href="{{ route('blog.archive.year', $year) }}"
                                       class="block font-thin text-lg leading-tight {{ $isYearActive ? 'text-blue-900 font-medium' : 'text-gray-800 hover:text-blue-900' }}">
                                        {{ $year }}
                                        <span class="text-xs text-gray-500 ml-0.5">({{ $yearTotal }})</span>
                                    </a>
                                    <div class="mt-1 pl-3 space-y-0.5">
                                        @foreach($months as $month => $monthCount)
                                            @php($isMonthActive = !empty($activeArchive) && $activeArchive['year'] === $year && $activeArchive['month'] === $month)
                                            <a href="{{ route('blog.archive.month', [$year, sprintf('%02d', $month)]) }}"
                                               class="block text-sm font-thin {{ $isMonthActive ? 'text-blue-900 font-medium' : 'text-gray-600 hover:text-blue-900' }}">
                                                {{ \Carbon\Carbon::create($year, $month, 1)->format('M') }}
                                                <span class="text-xs text-gray-500 ml-0.5">({{ $monthCount }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </nav>
                    </div>
                @endif

                {{-- Tags card. --}}
                @if(count($allTags) > 0)
                    <div class="bg-gray-100 rounded-lg p-4">
                        <div class="flex flex-wrap gap-x-1.5 gap-y-1 leading-relaxed">
                            @foreach($allTags as $tag => $count)
                                @php($isActive = $activeTag === $tag)
                                <a href="{{ \BristolDigital\QwikBlog\Support\BlogUrls::tag($tag) }}"
                                   class="text-sm {{ $isActive ? 'text-blue-900 font-medium' : 'text-blue-800 hover:text-blue-900 hover:underline' }}">#{{ $tag }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Search card. --}}
                <div class="bg-gray-100 rounded-lg p-4">
                    <form method="GET" action="{{ route('blog.search') }}">
                        <div class="flex gap-2">
                            <input
                                type="search"
                                name="q"
                                value="{{ $searchQuery ?? '' }}"
                                placeholder="Search..."
                                class="flex-1 min-w-0 px-3 py-2 text-sm bg-white border border-gray-300 rounded focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900"
                            >
                            <button type="submit" class="px-3 py-2 bg-blue-900 text-white rounded hover:bg-blue-800 transition-colors text-sm">
                                Go
                            </button>
                        </div>
                    </form>
                </div>

            </aside>

        </div>
    </div>
</section>

@endsection
