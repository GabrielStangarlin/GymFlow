@extends('layouts.crm')

@section('title', 'Novo funcionário - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Novo funcionário</h1>
    <form method="POST" action="{{ route('crm.staff.store') }}" class="space-y-4 max-w-md">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium">Nome</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Palavra-passe</label>
            <input id="password" name="password" type="password" required class="mt-1 w-full rounded border border-gray-300 p-2">
            @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirmar palavra-passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 w-full rounded border border-gray-300 p-2">
        </div>
        <button type="submit" class="rounded bg-gray-900 text-white px-4 py-2 disabled:opacity-50">Criar</button>
    </form>
@endsection
