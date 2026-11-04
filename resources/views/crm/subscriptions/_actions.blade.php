@php($state = $subscription->status())
<div class="flex flex-wrap gap-2">
    @if ($state->value !== 'ended')
        <form method="POST" action="{{ route('crm.subscriptions.pay', $subscription) }}" onsubmit="return confirm('Marcar esta assinatura como paga?')">@csrf<button type="submit" class="rounded border px-2 py-1 disabled:opacity-50">Marcar como pago</button></form>
    @endif
    @if ($subscription->hasOutstandingDebt())
        <form method="POST" action="{{ route('crm.subscriptions.settle-debt', $subscription) }}" onsubmit="return confirm('Marcar a dívida como paga?')">@csrf<button type="submit" class="rounded border px-2 py-1 disabled:opacity-50">Marcar dívida como paga</button></form>
    @endif
    @if ($state->value === 'pending')
        <form method="POST" action="{{ route('crm.subscriptions.cancel', $subscription) }}" onsubmit="return confirm('Cancelar esta assinatura pendente?')">@csrf<button type="submit" class="rounded border border-red-600 text-red-700 px-2 py-1 disabled:opacity-50">Cancelar</button></form>
    @endif
</div>
