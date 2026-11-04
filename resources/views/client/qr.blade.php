@extends('layouts.guest')

@section('title', 'O meu QR - GymFlow')

@section('content')
    <h2 class="text-xl font-bold text-center">O meu código QR</h2>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full rounded bg-gray-900 text-white p-2">Terminar sessão</button>
    </form>
@endsection
