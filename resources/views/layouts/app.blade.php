<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name')) &middot; {{ config('app.name') }}</title>

    {{-- RSS autodiscovery so feed readers find the blog feed automatically. --}}
    <link rel="alternate" type="application/rss+xml"
          title="{{ config('app.name') }}"
          href="{{ url('/blog/feed.xml') }}">

    {{-- CDN Tailwind + Typography + Alpine. Zero host-side setup required.
         Once you've got your own Tailwind build pipeline running, copy this
         layout into your host app's resources/views/app.blade.php, replace
         the CDN tags with @vite([...]) entries, and set QWIKBLOG_LAYOUT=app
         (or remove the env var, since 'app' is the default). --}}
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Blog injects OG/Twitter meta and per-page extras through this stack. --}}
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col">

<header class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-5 py-4 flex items-center justify-between">
        <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900 hover:text-blue-900">
            {{ config('app.name') }}
        </a>
        <nav class="flex items-center gap-6 text-sm">
            <a href="{{ url('/blog') }}" class="text-gray-600 hover:text-blue-900">Blog</a>
            <a href="{{ url('/blog/feed.xml') }}" class="text-gray-600 hover:text-blue-900">RSS</a>
        </nav>
    </div>
</header>

<main class="flex-1">
    @yield('content')
</main>

<footer class="bg-white border-t border-gray-200 mt-12">
    <div class="max-w-7xl mx-auto px-5 py-6 text-sm text-gray-500 flex justify-between items-center">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        <a href="{{ url('/blog/feed.xml') }}" class="hover:text-blue-900">RSS feed</a>
    </div>
</footer>

@stack('scripts')

</body>
</html>
