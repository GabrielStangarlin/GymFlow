@extends('layouts.crm')

@section('title', 'Assinaturas - GymFlow')

@section('content')
    @php
        $tabs = ['' => 'Todas', 'pending' => 'Pendentes', 'active' => 'Ativas', 'overdue' => 'Em atraso', 'ended' => 'Terminadas', 'debt' => 'Com dívida'];
        $pillClasses = ['pending' => 'bg-yellow-100 text-yellow-800', 'active' => 'bg-green-100 text-green-800', 'overdue' => 'bg-red-100 text-red-800', 'ended' => 'bg-gray-200 text-gray-700'];
    @endphp
    <h1 class="text-2xl font-bold mb-4">Assinaturas</h1>
    @error('subscription')<div class="mb-4 rounded bg-red-100 text-red-800 p-3">{{ $message }}</div>@enderror
    <nav class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('crm.subscriptions.index', $value === '' ? [] : ['status' => $value]) }}"
               class="rounded border px-3 py-1 {{ $status === $value ? 'bg-gray-900 text-white' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b"><th class="p-2">Aluno</th><th class="p-2">Plano</th><th class="p-2">Periodicidade</th><th class="p-2">Valor</th><th class="p-2">Estado</th><th class="p-2">Pago até</th><th class="p-2">Ações</th></tr>
        </thead>
        <tbody>
            @forelse ($subscriptions as $subscription)
                @php($state = $subscription->status())
                <tr class="border-b">
                    <td class="p-2"><a href="{{ route('crm.clients.show', $subscription->client) }}" class="underline">{{ $subscription->client->user->name }}</a></td>
                    <td class="p-2">{{ $subscription->plan->name }}</td>
                    <td class="p-2">{{ $subscription->billingPeriod->name }}</td>
                    <td class="p-2">{{ number_format($subscription->amountPerPeriod(), 2, ',', ' ') }} €</td>
                    <td class="p-2"><span class="rounded-full px-2 py-0.5 text-sm {{ $pillClasses[$state->value] }}">{{ $state->label() }}</span></td>
                    <td class="p-2">{{ $subscription->paid_until?->format('d/m/Y') ?? '—' }}</td>
                    <td class="p-2">
                        <div class="flex flex-wrap gap-2">
                            @if ($state->value !== 'ended')
                                <form method="POST" action="{{ route('crm.subscriptions.pay', $subscription) }}">@csrf<button type="submit" class="rounded border px-2 py-1 disabled:opacity-50">Marcar como pago</button></form>
                            @endif
                            @if ($subscription->hasOutstandingDebt())
                                <form method="POST" action="{{ route('crm.subscriptions.settle-debt', $subscription) }}">@csrf<button type="submit" class="rounded border px-2 py-1 disabled:opacity-50">Marcar dívida como paga</button></form>
                            @endif
                            @if ($state->value === 'pending')
                                <form method="POST" action="{{ route('crm.subscriptions.cancel', $subscription) }}">@csrf<button type="submit" class="rounded border border-red-600 text-red-700 px-2 py-1 disabled:opacity-50">Cancelar</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-2 text-gray-600">Nenhuma assinatura encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $subscriptions->links() }}</div>
@endsection
