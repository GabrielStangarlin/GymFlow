@extends('layouts.crm')

@section('title', 'Dashboard - GymFlow')

@section('content')
    @php
        $weekdays = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
        $maxPlan = max(1, ...array_values($clientsPerPlan ?: [0]));
        $maxHour = max(0.0001, ...array_map(fn ($hours) => max($hours), $peakHours));
        $totalLabels = ['upgrade' => 'Upgrades', 'downgrade' => 'Downgrades', 'lateral' => 'Mudanças laterais', 'renewal' => 'Renovações'];
        $matrixPlans = array_keys($clientsPerPlan);
    @endphp
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    @if ($errors->any())<div class="mb-4 rounded bg-red-100 text-red-800 p-3">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('crm.dashboard') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <label class="block text-sm">De<input type="date" name="from" value="{{ $from }}" class="block rounded border px-2 py-1"></label>
        <label class="block text-sm">Até<input type="date" name="to" value="{{ $to }}" class="block rounded border px-2 py-1"></label>
        <button type="submit" class="rounded border px-3 py-1">Filtrar</button>
        <a href="{{ route('crm.dashboard') }}" class="rounded border px-3 py-1">Limpar</a>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach (['active' => 'Ativos', 'overdue' => 'Em atraso', 'pending' => 'Pendentes', 'inside' => 'Dentro agora', 'refused' => 'Recusas no período'] as $key => $label)
            <div class="rounded bg-white p-4 shadow">
                <div class="text-3xl font-bold">{{ $counts[$key] }}</div>
                <div class="text-sm text-gray-600">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <section class="rounded bg-white p-4 shadow mb-6">
        <h2 class="font-semibold mb-2">Alunos por plano</h2>
        @forelse ($clientsPerPlan as $plan => $total)
            <div class="flex items-center gap-2 mb-1">
                <span class="w-32 text-sm truncate">{{ $plan }}</span>
                <div class="flex-1 bg-gray-100 rounded"><div class="bg-blue-600 rounded h-4" style="width: {{ $total / $maxPlan * 100 }}%"></div></div>
                <span class="w-8 text-right text-sm">{{ $total }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-600">Sem planos.</p>
        @endforelse
    </section>

    <section class="rounded bg-white p-4 shadow mb-6">
        <h2 class="font-semibold mb-2">Mudanças de plano</h2>
        <div class="flex flex-wrap gap-6 mb-4">
            @foreach ($totalLabels as $key => $label)
                <div><div class="text-2xl font-bold">{{ $planChanges['totals'][$key] }}</div><div class="text-sm text-gray-600">{{ $label }}</div></div>
            @endforeach
        </div>
        <table class="text-left text-sm">
            <thead>
                <tr class="border-b"><th class="p-2">De \ Para</th>@foreach ($matrixPlans as $toPlan)<th class="p-2">{{ $toPlan }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($matrixPlans as $fromPlan)
                    <tr class="border-b">
                        <th class="p-2">{{ $fromPlan }}</th>
                        @foreach ($matrixPlans as $toPlan)<td class="p-2">{{ $planChanges['matrix'][$fromPlan][$toPlan] ?? 0 }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="rounded bg-white p-4 shadow overflow-x-auto">
        <h2 class="font-semibold mb-2">Horas de pico (média de alunos presentes)</h2>
        <div class="grid gap-px text-xs" style="grid-template-columns: 2.5rem repeat(24, minmax(1.5rem, 1fr))">
            <div></div>
            @for ($hour = 0; $hour < 24; $hour++)<div class="text-center text-gray-600">{{ $hour }}</div>@endfor
            @foreach ($weekdays as $day => $label)
                <div class="text-gray-600">{{ $label }}</div>
                @for ($hour = 0; $hour < 24; $hour++)
                    <div class="h-6 rounded-sm bg-blue-600" style="opacity: {{ $peakHours[$day][$hour] / $maxHour }}" title="{{ $label }} {{ $hour }}h: {{ round($peakHours[$day][$hour], 1) }}"></div>
                @endfor
            @endforeach
        </div>
    </section>
@endsection
