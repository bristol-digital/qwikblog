<div wire:poll.30s class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Posts</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $publishedCount }} published &middot; {{ $scheduledCount }} scheduled &middot; {{ $totalCount }} total
            </p>
        </div>
        <a href="{{ route('admin.posts.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded text-sm font-medium transition-colors">
            + New Post
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Title, summary, author..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900"
                >
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                <select wire:model.live="category"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900">
                    <option value="">All categories</option>
                    @foreach($allCategories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tag</label>
                <select wire:model.live="tag"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900">
                    <option value="">All tags</option>
                    @foreach($allTags as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select wire:model.live="status"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900">
                    <option value="">All ({{ $totalCount }})</option>
                    <option value="published">Published ({{ $publishedCount }})</option>
                    <option value="scheduled">Scheduled ({{ $scheduledCount }})</option>
                </select>
            </div>

        </div>

        @if($hasFilters)
            <div class="mt-3 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    Showing <strong>{{ $filteredCount }}</strong>
                    of {{ $totalCount }} posts
                </span>
                <button wire:click="clearFilters"
                        class="text-blue-900 hover:underline font-medium">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Posts table --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($posts as $post)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $post->title }}</div>
                            @if($post->subtitle)
                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $post->subtitle }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(count($post->categories) > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($post->categories as $cat)
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $cat }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $post->author ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            {{ $post->date->format('Y-m-d') }}
                            @if($post->isScheduled())
                                <div class="text-xs text-amber-700 mt-0.5">
                                    {{ $post->date->diffForHumans() }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($post->isScheduled())
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                                    Scheduled
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Published
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <a href="{{ route('admin.posts.edit', $post->slug) }}"
                               class="text-blue-900 hover:underline mr-3">Edit</a>
                            <a href="{{ route('admin.posts.images', $post->slug) }}"
                               class="text-blue-900 hover:underline mr-3">Images</a>
                            <button wire:click="delete('{{ $post->slug }}')"
                                    wire:confirm="Delete this post? This also removes its image folder."
                                    class="text-red-700 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            @if($hasFilters)
                                No posts match your filters.
                                <button wire:click="clearFilters" class="text-blue-900 hover:underline ml-1">
                                    Clear filters
                                </button>
                            @else
                                No posts yet. <a href="{{ route('admin.posts.create') }}" class="text-blue-900 hover:underline">Create your first one</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
        <div>
            {{ $posts->links() }}
        </div>
    @endif

</div>
