@extends('layouts.crm')

@section('title', 'Novo plano - GymFlow')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Novo plano</h1>
    @include('crm.plans._form', ['action' => route('crm.plans.store')])
@endsection
