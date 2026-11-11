@php
    $menu = [
        ['Dashboard', 'crm.dashboard'],
        ['Ler QR', 'crm.scan.show'],
        ['Inscrições', 'crm.enrollments.create'],
        ['Alunos', 'crm.clients.index'],
        ['Planos', 'crm.plans.index'],
        ['Assinaturas', 'crm.subscriptions.index'],
        ['Entradas/Saídas', 'crm.access-logs.index'],
    ];
    if (auth()->user()->role === \App\Enums\Role::Admin) {
        $menu[] = ['Funcionários', 'crm.staff.index'];
    }
@endphp
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GymFlow')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="min-h-screen bg-gray-100 md:flex">
    <aside class="bg-gray-900 text-gray-100 md:w-60 md:min-h-screen p-4">
        <div class="text-xl font-bold mb-4">GymFlow</div>
        <nav class="flex flex-wrap md:flex-col gap-1">
            @foreach ($menu as [$label, $routeName])
                <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
                   class="px-3 py-2 rounded hover:bg-gray-700 {{ request()->routeIs($routeName) ? 'bg-gray-700' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="px-3 py-2 rounded bg-gray-700 hover:bg-gray-600 w-full text-left">Terminar sessão</button>
        </form>
    </aside>
    <main class="flex-1 p-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 text-green-800 p-3">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
    @include('layouts.partials.disable-submit')
</body>
</html>
