@extends('layouts.crm')

@section('title', 'Ler QR - GymFlow')

@push('scripts')
    @vite('resources/js/scanner.js')
@endpush

@section('content')
    <h1 class="text-2xl font-bold mb-4">Ler QR</h1>

    <div id="scanner" data-scan-url="{{ route('crm.scan.store') }}" class="max-w-md">
        <button type="button" id="scan-start" class="px-4 py-2 rounded bg-gray-900 text-white hover:bg-gray-700">Ler QR</button>
        <div id="scan-reader" class="mt-4"></div>
        <div id="scan-result" class="hidden mt-4 rounded p-6 text-center text-white" role="status" aria-live="polite"></div>
    </div>
@endsection
