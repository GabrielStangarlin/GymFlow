<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanRequest;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function store(ScanRequest $request, AccessService $accessService): JsonResponse
    {
        $log = $accessService->scan($request->validated('token'), $request->user());

        return response()->json([
            'type' => $log->type->value,
            'type_label' => $log->type->label(),
            'reason' => $log->refusal_reason?->value,
            'reason_label' => $log->refusal_reason?->label(),
            'client_name' => $log->client?->name,
            'scanned_at' => $log->scanned_at->toIso8601String(),
        ]);
    }
}
