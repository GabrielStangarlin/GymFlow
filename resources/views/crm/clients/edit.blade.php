@extends('layouts.crm')

@section('title', 'Editar aluno - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Editar aluno</h1>
    <form method="POST" action="{{ route('crm.clients.update', $client) }}" class="space-y-4 max-w-md">
        @csrf
        @method('PUT')
        @foreach ([['name', 'Nome', 'text', $client->user->name], ['email', 'Email', 'email', $client->user->email], ['phone', 'Telefone', 'tel', $client->phone], ['nif', 'NIF', 'text', $client->nif], ['birth_date', 'Data de nascimento', 'date', $client->birth_date->toDateString()]] as [$field, $label, $type, $value])
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $value) }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
                @error($field)<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <button type="submit" class="rounded bg-gray-900 text-white px-4 py-2 disabled:opacity-50">Guardar</button>
        <a href="{{ route('crm.clients.show', $client) }}" class="ml-2 underline">Cancelar</a>
    </form>
@endsection
