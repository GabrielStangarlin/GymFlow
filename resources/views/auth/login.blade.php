@extends('layouts.guest')

@section('title', 'Entrar - GymFlow')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Palavra-passe</label>
            <input id="password" name="password" type="password" required class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> Manter sessão iniciada
        </label>
        <button type="submit" class="w-full rounded bg-gray-900 text-white p-2 disabled:opacity-50">Entrar</button>
    </form>
@endsection
