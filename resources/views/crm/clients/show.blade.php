@extends('layouts.crm')

@section('title', $client->user->name.' - GymFlow')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h1 class="text-2xl font-bold">{{ $client->user->name }} @unless ($client->active)<span class="text-sm text-gray-500">(inativo)</span>@endunless</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('crm.clients.edit', $client) }}" class="rounded border px-3 py-2">Editar</a>
            @if ($client->active)
                <a href="{{ route('crm.clients.subscriptions.create', $client) }}" class="rounded border px-3 py-2">Nova assinatura</a>
            @endif
            <form method="POST" action="{{ route('crm.clients.resend-activation', $client) }}">
                @csrf
                <button type="submit" class="rounded border px-3 py-2 disabled:opacity-50">Reenviar email de ativação</button>
            </form>
            @if ($client->active)
                <form method="POST" action="{{ route('crm.clients.deactivate', $client) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded border border-red-600 text-red-700 px-3 py-2 disabled:opacity-50">Desativar</button>
                </form>
            @endif
        </div>
    </div>
    @error('subscription')<div class="mb-4 rounded bg-red-100 text-red-800 p-3">{{ $message }}</div>@enderror

    <h2 class="text-lg font-semibold mt-6 mb-2">Dados pessoais</h2>
    <dl class="grid grid-cols-2 max-w-md gap-1">
        <dt class="text-gray-600">Email</dt><dd>{{ $client->user->email }}</dd>
        <dt class="text-gray-600">Telefone</dt><dd>{{ $client->phone }}</dd>
        <dt class="text-gray-600">NIF</dt><dd>{{ $client->nif }}</dd>
        <dt class="text-gray-600">Data de nascimento</dt><dd>{{ $client->birth_date->format('d/m/Y') }}</dd>
    </dl>

    <h2 class="text-lg font-semibold mt-6 mb-2">Assinatura atual</h2>
    @if ($current)
        <p>{{ $current->plan->name }} · {{ $current->billingPeriod->name }}
            <span class="ml-2 rounded-full bg-gray-200 px-2 py-0.5 text-sm">{{ $current->status()->label() }}</span>
            @if ($current->paid_until) · pago até {{ $current->paid_until->format('d/m/Y') }}@endif</p>
    @else
        <p class="text-gray-600">Sem assinatura atual.</p>
    @endif

    <h2 class="text-lg font-semibold mt-6 mb-2">Histórico de assinaturas</h2>
    <table class="w-full text-left">
        <thead><tr class="border-b"><th class="p-2">Plano</th><th class="p-2">Periodicidade</th><th class="p-2">Mudança</th><th class="p-2">Início</th><th class="p-2">Pago até</th><th class="p-2">Fim</th></tr></thead>
        <tbody>
            @forelse ($client->subscriptions as $subscription)
                <tr class="border-b">
                    <td class="p-2">{{ $subscription->plan->name }}</td>
                    <td class="p-2">{{ $subscription->billingPeriod->name }}</td>
                    <td class="p-2">{{ $subscription->change_type->label() }}</td>
                    <td class="p-2">{{ $subscription->starts_on?->format('d/m/Y') ?? '—' }}</td>
                    <td class="p-2">{{ $subscription->paid_until?->format('d/m/Y') ?? '—' }}</td>
                    <td class="p-2">{{ $subscription->ended_on ? $subscription->ended_on->format('d/m/Y').' ('.$subscription->end_reason?->label().')' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-2 text-gray-600">Sem assinaturas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="text-lg font-semibold mt-6 mb-2">Pagamentos</h2>
    <table class="w-full text-left">
        <thead><tr class="border-b"><th class="p-2">Data</th><th class="p-2">Valor</th><th class="p-2">Tipo</th><th class="p-2">Período</th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr class="border-b">
                    <td class="p-2">{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                    <td class="p-2">{{ number_format((float) $payment->amount, 2, ',', ' ') }} €</td>
                    <td class="p-2">{{ $payment->type->value === 'debt' ? 'Dívida' : 'Período' }}</td>
                    <td class="p-2">{{ $payment->period_start ? $payment->period_start->format('d/m/Y').' – '.$payment->period_end->format('d/m/Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-2 text-gray-600">Sem pagamentos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="text-lg font-semibold mt-6 mb-2">Últimos acessos</h2>
    <table class="w-full text-left">
        <thead><tr class="border-b"><th class="p-2">Data</th><th class="p-2">Tipo</th><th class="p-2">Motivo da recusa</th></tr></thead>
        <tbody>
            @forelse ($client->accessLogs as $log)
                <tr class="border-b">
                    <td class="p-2">{{ $log->scanned_at->format('d/m/Y H:i') }}</td>
                    <td class="p-2">{{ $log->type->label() }}</td>
                    <td class="p-2">{{ $log->refusal_reason?->label() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="p-2 text-gray-600">Sem acessos.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
