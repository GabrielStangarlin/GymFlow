@extends('layouts.crm')

@section('title', 'Editar plano - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Editar plano</h1>
    @include('crm.plans._form', ['action' => route('crm.plans.update', $plan)])
@endsection
