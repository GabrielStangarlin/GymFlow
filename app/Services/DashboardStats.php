<?php

namespace App\Services;

use App\Enums\AccessType;
use App\Enums\ChangeType;
use App\Enums\PaymentType;
use App\Models\AccessLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\CarbonInterface;

class DashboardStats
{
    public readonly CarbonInterface $from;

    public readonly CarbonInterface $to;

    public function __construct(?CarbonInterface $from = null, ?CarbonInterface $to = null)
    {
        $this->to = ($to ?? now())->copy()->endOfDay();
        $this->from = ($from ?? $this->to->copy()->subDays(29))->copy()->startOfDay();
    }

    /**
     * @return array{active: int, overdue: int, pending: int, inside: int, refused: int}
     */
    public function counts(): array
    {
        $inside = AccessLog::whereIn('type', [AccessType::Entry, AccessType::Exit])
            ->whereBetween('scanned_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scanned_at')->orderBy('id')
            ->get()
            ->groupBy('client_id')
            ->filter(fn ($logs) => $logs->last()->type === AccessType::Entry)
            ->count();

        return [
            'active' => Subscription::withStatus('active')->count(),
            'overdue' => Subscription::withStatus('overdue')->count(),
            'pending' => Subscription::withStatus('pending')->count(),
            'inside' => $inside,
            'refused' => AccessLog::where('type', AccessType::Refused)->whereBetween('scanned_at', [$this->from, $this->to])->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function clientsPerPlan(): array
    {
        $counts = Subscription::withStatus('active')->selectRaw('plan_id, count(*) as total')->groupBy('plan_id')->pluck('total', 'plan_id');

        return Plan::orderBy('name')->get()->mapWithKeys(fn (Plan $plan) => [$plan->name => (int) ($counts[$plan->id] ?? 0)])->all();
    }

    /**
     * @return array{totals: array{upgrade: int, downgrade: int, lateral: int, renewal: int}, matrix: array<string, array<string, int>>}
     */
    public function planChanges(): array
    {
        $subscriptions = Subscription::with(['plan', 'previous.plan'])
            ->whereBetween('created_at', [$this->from, $this->to])->get();

        $totals = [];
        foreach ([ChangeType::Upgrade, ChangeType::Downgrade, ChangeType::Lateral, ChangeType::Renewal] as $type) {
            $totals[$type->value] = $subscriptions->where('change_type', $type)->count();
        }

        $totals['renewal'] += Payment::with('subscription')
            ->where('type', PaymentType::Period)
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->get()
            ->filter(fn (Payment $payment) => $payment->period_start > $payment->subscription->starts_on)
            ->count();

        $matrix = [];
        foreach ($subscriptions->whereIn('change_type', [ChangeType::Upgrade, ChangeType::Downgrade, ChangeType::Lateral]) as $subscription) {
            if ($subscription->previous !== null) {
                $from = $subscription->previous->plan->name;
                $matrix[$from][$subscription->plan->name] = ($matrix[$from][$subscription->plan->name] ?? 0) + 1;
            }
        }

        return ['totals' => $totals, 'matrix' => $matrix];
    }

    /**
     * Average number of clients present per ISO weekday and hour.
     *
     * @return array<int, array<int, float>>
     */
    public function peakHours(): array
    {
        $presences = array_fill(1, 7, array_fill(0, 24, 0));

        $logs = AccessLog::whereIn('type', [AccessType::Entry, AccessType::Exit])
            ->whereBetween('scanned_at', [$this->from, $this->to])
            ->orderBy('scanned_at')->orderBy('id')->get()
            ->groupBy(fn (AccessLog $log) => $log->client_id.'|'.$log->scanned_at->toDateString());

        foreach ($logs as $dayLogs) {
            $counted = [];
            foreach ($dayLogs->values() as $index => $log) {
                if ($log->type !== AccessType::Entry) {
                    continue;
                }
                $next = $dayLogs->values()[$index + 1] ?? null;
                $end = $next?->type === AccessType::Exit
                    ? $next->scanned_at
                    : $log->scanned_at->copy()->addHours(2)->min($log->scanned_at->copy()->endOfDay());

                $lastHour = $end->hour;
                if ($end->minute === 0 && $end->second === 0 && $end > $log->scanned_at) {
                    $lastHour--;
                }

                for ($hour = $log->scanned_at->hour; $hour <= $lastHour; $hour++) {
                    if (! isset($counted[$hour])) {
                        $counted[$hour] = true;
                        $presences[$log->scanned_at->isoWeekday()][$hour]++;
                    }
                }
            }
        }

        $occurrences = array_fill(1, 7, 0);
        for ($day = $this->from->copy()->startOfDay(); $day <= $this->to; $day->addDay()) {
            $occurrences[$day->isoWeekday()]++;
        }

        foreach ($presences as $weekday => $hours) {
            foreach ($hours as $hour => $count) {
                $presences[$weekday][$hour] = $occurrences[$weekday] > 0 ? (float) ($count / $occurrences[$weekday]) : 0.0;
            }
        }

        return $presences;
    }
}
