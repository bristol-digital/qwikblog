<div class="max-w-4xl">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.posts.index') }}" class="text-slate-700 hover:text-slate-900 text-sm">
                &larr; Back to Posts
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">
                Manage Images: {{ $post['title'] }}
            </h1>
            <p class="text-gray-500 text-sm">{{ $post['date'] }}</p>
        </div>
        <a
            href="{{ route('admin.posts.edit', $slug) }}"
            class="text-sm border px-4 py-2 rounded hover:bg-gray-50"
        >
            Back to post form
        </a>
    </div>

    {{-- Upload Section --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Upload Images</h2>

        <div
            x-data="{ dragging: false }"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            :class="{ 'border-slate-500 bg-slate-50': dragging }"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-colors"
        >
            <input
                type="file"
                x-ref="fileInput"
                wire:model="uploads"
                multiple
                accept=".jpg,.jpeg,.png,.gif"
                class="hidden"
            >

            <div wire:loading.remove wire:target="uploads">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="mt-2 text-gray-600">
                    Drag &amp; drop images here, or
                    <button
                        type="button"
                        x-on:click="$refs.fileInput.click()"
                        class="text-slate-700 hover:text-slate-900 font-medium"
                    >
                        browse
                    </button>
                </p>
                <p class="text-xs text-gray-500 mt-1">JPG, PNG or GIF. Max 20MB each. Images are resized and optimised automatically (GIFs keep their animation).</p>
            </div>

            <div wire:loading wire:target="uploads" class="py-4">
                <svg class="animate-spin h-8 w-8 text-slate-700 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-600">Uploading and processing&hellip;</p>
            </div>
        </div>

        @error('uploads.*')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Current Images --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                Current Images ({{ count($images) }})
            </h2>
            @if(count($images) > 1)
                <p class="text-sm text-gray-500">Drag to reorder.</p>
            @endif
        </div>

        @if(count($images) > 0)
            <div
                x-data="{
                    reorder(event) {
                        const items = Array.from(event.target.closest('ul').children);
                        const order = items.map(item => item.dataset.filename);
                        $wire.reorderImages(order);
                    }
                }"
                x-init="
                    new Sortable($el.querySelector('ul'), {
                        animation: 150,
                        handle: '.drag-handle',
                        onEnd: (e) => reorder(e)
                    })
                "
            >
                <ul class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($images as $index => $image)
                        <li
                            data-filename="{{ $image['filename'] }}"
                            wire:key="image-{{ $image['filename'] }}"
                            class="relative group bg-gray-100 rounded-lg overflow-hidden"
                        >
                            <div class="aspect-[4/3]">
                                <img
                                    src="{{ $image['path'] }}?v={{ time() }}"
                                    alt=""
                                    class="w-full h-full object-cover"
                                >
                            </div>

                            {{-- Hover overlay with actions --}}
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="drag-handle p-2 bg-white rounded-lg text-gray-700 hover:bg-gray-100 cursor-move"
                                    title="Drag to reorder"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </button>

                                {{-- Copy path button — handy for pasting into the hero_image field --}}
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="navigator.clipboard.writeText('{{ $image['path'] }}'); $el.innerText = '✓'; setTimeout(() => $el.innerHTML = $el.dataset.original, 1500)"
                                    data-original='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>'
                                    class="p-2 bg-white rounded-lg text-gray-700 hover:bg-gray-100"
                                    title="Copy path (paste into the post's hero image field)"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteImage('{{ $image['filename'] }}')"
                                    wire:confirm="Delete this image?"
                                    class="p-2 bg-red-600 rounded-lg text-white hover:bg-red-700"
                                    title="Delete image"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>

                            @if($index === 0)
                                <span class="absolute top-2 left-2 bg-slate-800 text-white text-xs px-2 py-1 rounded">
                                    First
                                </span>
                            @endif

                            <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white text-xs p-2">
                                <p class="truncate font-mono">{{ $image['path'] }}</p>
                                <p class="text-gray-400">{{ $image['size'] }} KB</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-2">No images yet. Upload some above.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
