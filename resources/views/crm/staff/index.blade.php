@extends('layouts.crm')

@section('title', 'Funcionários - GymFlow')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Funcionários</h1>
        <a href="{{ route('crm.staff.create') }}" class="rounded bg-gray-900 text-white px-3 py-2">Novo funcionário</a>
    </div>
    @error('user')<div class="mb-4 rounded bg-red-100 text-red-800 p-3">{{ $message }}</div>@enderror
    <table class="w-full text-left">
        <thead>
            <tr class="border-b"><th class="p-2">Nome</th><th class="p-2">Email</th><th class="p-2">Papel</th><th class="p-2">Estado</th><th class="p-2"></th></tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-b">
                    <td class="p-2">{{ $user->name }}</td>
                    <td class="p-2">{{ $user->email }}</td>
                    <td class="p-2">{{ $user->role->label() }}</td>
                    <td class="p-2">{{ $user->active ? 'Ativo' : 'Inativo' }}</td>
                    <td class="p-2">
                        <form method="POST" action="{{ route($user->active ? 'crm.staff.deactivate' : 'crm.staff.activate', $user) }}" @if ($user->active) onsubmit="return confirm('Desativar este funcionário?')" @endif>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded border px-3 py-1 disabled:opacity-50">{{ $user->active ? 'Desativar' : 'Ativar' }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
