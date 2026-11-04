<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GymFlow')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">GymFlow</h1>
        @yield('content')
    </main>
    @include('layouts.partials.disable-submit')
</body>
</html>
