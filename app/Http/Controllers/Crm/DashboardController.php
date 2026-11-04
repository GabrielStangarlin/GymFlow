<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardFilterRequest;
use App\Services\DashboardStats;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardFilterRequest $request): View
    {
        $filters = $request->validated();
        $stats = new DashboardStats(
            isset($filters['from']) ? Carbon::parse($filters['from']) : null,
            isset($filters['to']) ? Carbon::parse($filters['to']) : null,
        );

        return view('crm.dashboard', [
            'from' => $stats->from->toDateString(),
            'to' => $stats->to->toDateString(),
            'counts' => $stats->counts(),
            'clientsPerPlan' => $stats->clientsPerPlan(),
            'planChanges' => $stats->planChanges(),
            'peakHours' => $stats->peakHours(),
        ]);
    }
}
