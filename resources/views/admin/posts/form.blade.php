<x-qwikblog::admin.layout :title="$post ? 'Edit Post' : 'New Post'">
    @php($selectedCategories = old('categories') !== null ? \BristolDigital\QwikBlog\ValueObjects\BlogPost::parseList(old('categories')) : ($post?->categories ?? []))
    @php($selectedTags = old('tags') !== null ? \BristolDigital\QwikBlog\ValueObjects\BlogPost::parseList(old('tags')) : ($post?->tags ?? []))
    @php($allCategories = $allCategories ?? [])
    @php($allTags = $allTags ?? [])

    {{-- Toast UI Editor is bundled via Vite (resources/js/qwikblog-admin.js) and
         exposed on window.toastui by the admin layout. No CDN required.
         If the bundle fails to load (build not run, etc.), the textarea below
         stays styled and visible as a working markdown fallback. --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const textarea = document.getElementById('body');
                const editorEl = document.getElementById('body-editor');

                if (!textarea || !editorEl) {
                    return;
                }

                if (typeof toastui === 'undefined' || !toastui.Editor) {
                    console.error('[qwikblog] Toast UI Editor not found on window. Did npm install + npm run dev/build run? Falling back to plain textarea.');
                    editorEl.style.display = 'none';
                    return;
                }

                try {
                    const editor = new toastui.Editor({
                        el: editorEl,
                        height: '550px',
                        initialEditType: 'wysiwyg',     // 'wysiwyg' or 'markdown' — user can switch via toolbar
                        previewStyle: 'vertical',
                        initialValue: textarea.value,
                        usageStatistics: false,
                        autofocus: false,
                    });

                    // Hide the textarea only AFTER the editor is up. This way a
                    // future init failure never leaves the user staring at an empty container.
                    textarea.style.display = 'none';

                    // Sync editor → textarea on every keystroke so 'required'
                    // browser validation and any other JS reading textarea.value
                    // stay accurate.
                    editor.on('change', function () {
                        textarea.value = editor.getMarkdown();
                    });

                    // Belt-and-braces: also sync immediately before submit.
                    textarea.form.addEventListener('submit', function () {
                        textarea.value = editor.getMarkdown();
                    });
                } catch (e) {
                    console.error('[qwikblog] Toast UI init failed:', e);
                    editorEl.style.display = 'none';
                }
            });
        </script>
    @endpush

    <div class="max-w-3xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            {{ $post ? 'Edit Post' : 'New Post' }}
        </h1>

        @if($post && $post->isScheduled())
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 mb-6">
                <strong>Scheduled.</strong>
                This post will go live {{ $post->date->diffForHumans() }} ({{ $post->date->format('jS M Y, H:i') }}).
                Public visitors see 404 until then; you can preview via the View button.
            </div>
        @endif

        <form
            method="POST"
            action="{{ $post ? route('admin.posts.update', $post->slug) : route('admin.posts.store') }}"
            class="bg-white rounded-lg shadow p-6 space-y-6"
        >
            @csrf
            @if($post)
                @method('PUT')
            @endif

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Title *
                </label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    value="{{ old('title', $post?->title ?? '') }}"
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                    required
                >
                <p class="text-xs text-gray-500 mt-1">Determines the URL slug.</p>
            </div>

            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-1">
                    Subtitle
                </label>
                <input
                    type="text"
                    name="subtitle"
                    id="subtitle"
                    value="{{ old('subtitle', $post?->subtitle ?? '') }}"
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                >
            </div>

            <div>
                <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">
                    Summary *
                </label>
                <textarea
                    name="summary"
                    id="summary"
                    rows="3"
                    maxlength="1000"
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                    required
                >{{ old('summary', $post?->summary ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Short excerpt shown on listings.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Categories
                </label>
                <x-qwikblog::admin.chip-input
                    name="categories"
                    :selected="$selectedCategories"
                    :available="$allCategories"
                    placeholder="Type a category and press Enter, or use commas to separate multiple"
                    chip-class="bg-blue-100 text-blue-800"
                    chip-button-class="text-blue-700 hover:text-blue-900"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tags
                </label>
                <x-qwikblog::admin.chip-input
                    name="tags"
                    :selected="$selectedTags"
                    :available="$allTags"
                    placeholder="Type a tag and press Enter, or use commas to separate multiple"
                    chip-class="bg-emerald-100 text-emerald-800"
                    chip-button-class="text-emerald-700 hover:text-emerald-900"
                />
            </div>

            <div>
                <label for="hero_image" class="block text-sm font-medium text-gray-700 mb-1">
                    Hero image path
                </label>
                <input
                    type="text"
                    name="hero_image"
                    id="hero_image"
                    value="{{ old('hero_image', $post?->heroImage ?? '') }}"
                    placeholder="/images/blog/{{ $post?->slug ?? 'your-post' }}/1.jpg"
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    Path relative to <code>public/</code>.
                    @if($post)
                        Use the <a class="text-blue-600 hover:underline" href="{{ route('admin.posts.images', $post->slug) }}">image gallery</a>
                        to upload, then copy a path with the copy button. Leave blank to use the first uploaded image automatically.
                    @else
                        After creating the post you'll be able to upload images. Leave blank to use the first one automatically.
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">
                        Author *
                    </label>
                    <input
                        type="text"
                        name="author"
                        id="author"
                        value="{{ old('author', $post?->author ?? '') }}"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                        required
                    >
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                        Publish date &amp; time *
                    </label>
                    <input
                        type="datetime-local"
                        name="date"
                        id="date"
                        value="{{ old('date', $post?->date->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        Set in the future to schedule. Posts go live automatically once their date passes.
                    </p>
                </div>
            </div>

            {{-- Body: Toast UI Editor (WYSIWYG) renders into #body-editor on
                 page load and hides the textarea below. If anything fails the
                 textarea stays visible and styled so you can still post. --}}
            <div>
                <label for="body-editor" class="block text-sm font-medium text-gray-700 mb-1">
                    Body *
                </label>
                <div id="body-editor"></div>
                <textarea
                    name="body"
                    id="body"
                    rows="18"
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500 font-mono text-sm"
                    required
                >{{ old('body', $post?->rawBody() ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Rich-text editor with a Markdown toggle in the toolbar. If the editor doesn't load, the textarea above is a working markdown fallback.
                    @if($post)
                        For images, upload via the <a class="text-blue-600 hover:underline" href="{{ route('admin.posts.images', $post->slug) }}">image gallery</a> and paste the path.
                    @endif
                </p>
            </div>

            <div class="flex gap-4 pt-4 border-t">
                <button
                    type="submit"
                    class="bg-slate-800 text-white px-6 py-2 rounded hover:bg-slate-900 transition-colors"
                >
                    {{ $post ? 'Update Post' : 'Create Post' }}
                </button>
                <a href="{{ route('admin.posts.index') }}"
                    class="px-6 py-2 border rounded hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </a>
                @if($post)
                    <a
                        href="{{ route('admin.posts.images', $post->slug) }}"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    >
                        Manage Images &rarr;
                    </a>
                    <a
                        href="{{ route('blog.show', $post->slug) }}"
                        target="_blank"
                        class="px-6 py-2 border rounded hover:bg-gray-50 transition-colors ml-auto"
                    >
                        {{ $post->isScheduled() ? 'Preview' : 'View on site' }} &rarr;
                    </a>
                @endif
            </div>
        </form>

        @if(!$post)
            <p class="mt-4 text-sm text-gray-500">
                After creating the post, you'll be able to upload images.
            </p>
        @endif
    </div>
</x-qwikblog::admin.layout>
