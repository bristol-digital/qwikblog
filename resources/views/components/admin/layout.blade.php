<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Admin' }} &middot; {{ config('app.name') }} Admin</title>

    {{-- CDN Tailwind keeps the admin self-contained and independent of the
         host site's Vite build for styling. --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Vite-bundled admin JS — currently brings in Toast UI Editor for the
         post form's WYSIWYG body. Loaded only here, never on public pages.

         The host app needs to:
           1. `php artisan vendor:publish --tag=qwikblog-admin-js`
              (puts resources/js/qwikblog-admin.js in the host app)
           2. `npm install @toast-ui/editor`
           3. Add 'resources/js/qwikblog-admin.js' to vite.config.js inputs
           4. `npm run dev` (or build)

         See the README for the full setup. --}}
    @vite(['resources/js/qwikblog-admin.js'])

    {{-- Note: do NOT load Alpine separately here. Livewire's @livewireScripts
         bundles Alpine; loading a second copy makes wire:model on file inputs
         silently no-op (the image gallery's "browse and nothing happens" bug). --}}
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
<header class="bg-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="{{ route('admin.posts.index') }}" class="font-bold text-lg">
            {{ config('app.name') }} Admin
        </a>
        <div class="flex items-center gap-6">
            <a href="{{ route('blog.index') }}" target="_blank" class="text-slate-300 hover:text-white text-sm">
                View Blog &rarr;
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-slate-300 hover:text-white text-sm">
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>

@if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 max-w-7xl mx-auto mt-4">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 max-w-7xl mx-auto mt-4">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<main class="max-w-7xl mx-auto px-4 py-8">
    {{ $slot }}
</main>

@livewireScripts
@stack('scripts')
</body>
</html>
