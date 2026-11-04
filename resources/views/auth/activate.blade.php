@extends('layouts.guest')

@section('title', 'Ativar conta - GymFlow')

@section('content')
    <p class="mb-4 text-sm text-gray-600">Defina a sua palavra-passe para ativar a conta.</p>
    <form method="POST" action="{{ route('activation.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Palavra-passe</label>
            <input id="password" name="password" type="password" required autofocus class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirmar palavra-passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 w-full rounded border border-gray-300 p-2">
        </div>
        <button type="submit" class="w-full rounded bg-gray-900 text-white p-2 disabled:opacity-50">Ativar conta</button>
    </form>
@endsection
