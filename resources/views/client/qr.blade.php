@extends('layouts.guest')

@section('title', 'O meu QR - GymFlow')

@section('content')
    <h2 class="text-xl font-bold text-center">{{ $client->name }}</h2>
    <div class="mt-6 flex justify-center [&>svg]:w-full [&>svg]:h-auto [&>svg]:max-w-xs">{!! $client->qrSvg() !!}</div>
    <p class="mt-4 text-center text-gray-600">Mostre este código na receção.</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full rounded bg-gray-900 text-white p-2">Terminar sessão</button>
    </form>
@endsection
