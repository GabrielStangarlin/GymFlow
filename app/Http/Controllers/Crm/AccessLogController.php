<?php

namespace App\Http\Controllers\Crm;

use App\Enums\AccessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccessLogFilterRequest;
use App\Models\AccessLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class AccessLogController extends Controller
{
    public function index(AccessLogFilterRequest $request): View
    {
        $filters = $request->validated();
        $type = AccessType::tryFrom((string) $request->query('type'));

        $logs = AccessLog::with(['client.user', 'scanner'])
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('scanned_at', $date))
            ->when($filters['client'] ?? null, fn (Builder $query, string $term) => $query->whereHas('client', fn (Builder $client) => $client
                ->where('nif', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%"))))
            ->when($type, fn (Builder $query, AccessType $type) => $query->where('type', $type))
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('crm.access-logs.index', [
            'logs' => $logs,
            'date' => $filters['date'] ?? '',
            'client' => $filters['client'] ?? '',
            'type' => $type?->value ?? '',
        ]);
    }
}
