@extends('layouts.crm')

@section('title', 'Alunos - GymFlow')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Alunos</h1>
        <a href="{{ route('crm.enrollments.create') }}" class="rounded bg-gray-900 text-white px-3 py-2">Inscrever aluno</a>
    </div>
    <form method="GET" action="{{ route('crm.clients.index') }}" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $term }}" placeholder="Nome, email ou NIF" class="w-full max-w-md rounded border border-gray-300 p-2">
        <button type="submit" class="rounded border px-3 py-2">Pesquisar</button>
    </form>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b"><th class="p-2">Nome</th><th class="p-2">Email</th><th class="p-2">NIF</th><th class="p-2">Assinatura</th><th class="p-2">Estado</th></tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                @php($current = $client->subscriptions->first())
                <tr class="border-b">
                    <td class="p-2"><a href="{{ route('crm.clients.show', $client) }}" class="underline">{{ $client->user->name }}</a></td>
                    <td class="p-2">{{ $client->user->email }}</td>
                    <td class="p-2">{{ $client->nif }}</td>
                    <td class="p-2">{{ $current ? $current->plan->name.' ('.$current->status()->label().')' : 'Sem assinatura' }}</td>
                    <td class="p-2">{{ $client->active ? 'Ativo' : 'Inativo' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-2 text-gray-600">Nenhum aluno encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $clients->links() }}</div>
@endsection
