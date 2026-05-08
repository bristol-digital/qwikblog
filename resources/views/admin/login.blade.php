<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login &middot; {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Admin Login</h1>

    @if($errors->any())
        <div class="bg-red-50 text-red-600 p-4 rounded mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        <div class="mb-4">
            <label for="username" class="block text-gray-700 mb-2">Username</label>
            <input type="text" name="username" id="username"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                   required autofocus>
        </div>

        <div class="mb-6">
            <label for="password" class="block text-gray-700 mb-2">Password</label>
            <input type="password" name="password" id="password"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-slate-500"
                   required>
        </div>

        <button type="submit"
                class="w-full bg-slate-700 text-white py-2 rounded hover:bg-slate-800 transition-colors">
            Login
        </button>
    </form>
</div>
</body>
</html>
