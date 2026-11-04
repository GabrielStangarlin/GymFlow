@extends('layouts.crm')

@section('title', 'Planos - GymFlow')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Planos</h1>
        <div class="flex gap-2">
            <a href="{{ route('crm.billing-periods.edit') }}" class="rounded border px-3 py-2">Periodicidades</a>
            <a href="{{ route('crm.plans.create') }}" class="rounded bg-gray-900 text-white px-3 py-2">Novo plano</a>
        </div>
    </div>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b"><th class="p-2">Nome</th><th class="p-2">Preço mensal</th><th class="p-2">Horários</th><th class="p-2">Estado</th><th class="p-2"></th></tr>
        </thead>
        <tbody>
            @foreach ($plans as $plan)
                <tr class="border-b">
                    <td class="p-2">{{ $plan->name }}</td>
                    <td class="p-2">{{ number_format((float) $plan->monthly_price, 2, ',', '.') }} €</td>
                    <td class="p-2">{{ $plan->timeWindows->map(fn ($w) => substr($w->starts_at, 0, 5).'–'.substr($w->ends_at, 0, 5))->implode(', ') }}</td>
                    <td class="p-2">{{ $plan->active ? 'Ativo' : 'Inativo' }}</td>
                    <td class="p-2"><a href="{{ route('crm.plans.edit', $plan) }}" class="underline">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
