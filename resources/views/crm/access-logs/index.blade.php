@extends('layouts.crm')

@section('title', 'Entradas/Saídas - GymFlow')

@section('content')
    @php
        $types = ['entry' => 'Entrada', 'exit' => 'Saída', 'refused' => 'Recusado'];
        $pillClasses = ['entry' => 'bg-green-100 text-green-800', 'exit' => 'bg-blue-100 text-blue-800', 'refused' => 'bg-red-100 text-red-800'];
    @endphp
    <h1 class="text-2xl font-bold mb-4">Entradas/Saídas</h1>
    @if ($errors->any())<div class="mb-4 rounded bg-red-100 text-red-800 p-3">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('crm.access-logs.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <label class="block text-sm">Data<input type="date" name="date" value="{{ $date }}" class="block rounded border px-2 py-1"></label>
        <label class="block text-sm">Aluno (nome ou NIF)<input type="text" name="client" value="{{ $client }}" maxlength="100" class="block rounded border px-2 py-1"></label>
        <label class="block text-sm">Tipo
            <select name="type" class="block rounded border px-2 py-1">
                <option value="">Todos</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded border px-3 py-1">Filtrar</button>
        <a href="{{ route('crm.access-logs.index') }}" class="rounded border px-3 py-1">Limpar</a>
    </form>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b"><th class="p-2">Data/hora</th><th class="p-2">Aluno</th><th class="p-2">Tipo</th><th class="p-2">Motivo</th><th class="p-2">Lido por</th></tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="border-b">
                    <td class="p-2">{{ $log->scanned_at->format('d/m/Y H:i:s') }}</td>
                    <td class="p-2">
                        @if ($log->client)
                            <a href="{{ route('crm.clients.show', $log->client) }}" class="underline">{{ $log->client->user->name }}</a>
                        @else
                            Desconhecido
                        @endif
                    </td>
                    <td class="p-2"><span class="rounded-full px-2 py-0.5 text-sm {{ $pillClasses[$log->type->value] }}">{{ $log->type->label() }}</span></td>
                    <td class="p-2">{{ $log->refusal_reason?->label() ?? '—' }}</td>
                    <td class="p-2">{{ $log->scanner?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-2 text-gray-600">Nenhum registo encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
