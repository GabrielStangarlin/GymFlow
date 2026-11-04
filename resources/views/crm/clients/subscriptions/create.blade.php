@extends('layouts.crm')

@section('title', 'Nova assinatura - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-1">Nova assinatura</h1>
    <p class="mb-4 text-gray-600">{{ $client->user->name }}</p>
    @error('subscription')<div class="mb-4 max-w-md rounded bg-red-100 text-red-800 p-3">{{ $message }}</div>@enderror
    <form method="POST" action="{{ route('crm.clients.subscriptions.store', $client) }}" class="space-y-4 max-w-md">
        @csrf
        <div>
            <label for="plan_id" class="block text-sm font-medium">Plano</label>
            <select id="plan_id" name="plan_id" required class="mt-1 w-full rounded border border-gray-300 p-2">
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
            @error('plan_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="billing_period_id" class="block text-sm font-medium">Periodicidade</label>
            <select id="billing_period_id" name="billing_period_id" required class="mt-1 w-full rounded border border-gray-300 p-2">
                @foreach ($billingPeriods as $period)
                    <option value="{{ $period->id }}" @selected(old('billing_period_id') == $period->id)>{{ $period->name }}@if ($period->discount_percent > 0) (−{{ rtrim(rtrim(number_format($period->discount_percent, 2, ',', ''), '0'), ',') }}%)@endif</option>
                @endforeach
            </select>
            @error('billing_period_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="payment_method" class="block text-sm font-medium">Método de pagamento</label>
            <select id="payment_method" name="payment_method" required class="mt-1 w-full rounded border border-gray-300 p-2">
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
            @error('payment_method')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="paid_now" value="0">
            <input type="checkbox" name="paid_now" value="1" @checked(old('paid_now'))> Pago agora
        </label>
        <button type="submit" class="rounded bg-gray-900 text-white px-4 py-2 disabled:opacity-50">Criar assinatura</button>
    </form>
@endsection
