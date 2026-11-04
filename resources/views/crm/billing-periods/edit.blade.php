@extends('layouts.crm')

@section('title', 'Periodicidades - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Periodicidades</h1>
    <form method="POST" action="{{ route('crm.billing-periods.update') }}" class="space-y-4 max-w-md">
        @csrf
        @method('PUT')
        @foreach ($periods as $period)
            <div>
                <label for="discount-{{ $period->id }}" class="block text-sm font-medium">{{ $period->name }} ({{ $period->months }} {{ $period->months === 1 ? 'mês' : 'meses' }}) - desconto (%)</label>
                <input id="discount-{{ $period->id }}" name="discounts[{{ $period->id }}]" type="number" step="0.01" min="0" max="100" value="{{ old("discounts.{$period->id}", $period->discount_percent) }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
                @error("discounts.{$period->id}")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <button type="submit" class="rounded bg-gray-900 text-white px-4 py-2 disabled:opacity-50">Guardar</button>
    </form>
@endsection
