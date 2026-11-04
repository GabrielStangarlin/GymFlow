<?php

namespace App\Services;

use App\Enums\AccessType;
use App\Enums\RefusalReason;
use App\Enums\SubscriptionStatus;
use App\Models\AccessLog;
use App\Models\Client;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AccessService
{
    public function scan(string $qrToken, User $scannedBy, ?CarbonInterface $at = null): AccessLog
    {
        $at ??= now();

        return DB::transaction(function () use ($qrToken, $scannedBy, $at): AccessLog {
            $client = Client::where('qr_token', $qrToken)->lockForUpdate()->first();

            if ($client === null || ! $client->active) {
                return $this->record($client, AccessType::Refused, $scannedBy, $at, RefusalReason::Unknown);
            }

            $last = $client->accessLogs()
                ->where('type', '!=', AccessType::Refused)
                ->latest('scanned_at')
                ->latest('id')
                ->first();

            if ($last !== null && $last->scanned_at->diffInSeconds($at, true) < 60) {
                return $last;
            }

            if ($last !== null && $last->type === AccessType::Entry && $last->scanned_at->isSameDay($at)) {
                return $this->record($client, AccessType::Exit, $scannedBy, $at);
            }

            $subscription = $client->currentSubscription();
            $reason = match (true) {
                $subscription === null => RefusalReason::NoSubscription,
                $subscription->status($at) === SubscriptionStatus::Pending => RefusalReason::Pending,
                $subscription->status($at) === SubscriptionStatus::Overdue => RefusalReason::Overdue,
                ! $subscription->plan->allowsTime($at) => RefusalReason::OutsideHours,
                default => null,
            };

            return $reason === null
                ? $this->record($client, AccessType::Entry, $scannedBy, $at)
                : $this->record($client, AccessType::Refused, $scannedBy, $at, $reason);
        });
    }

    private function record(?Client $client, AccessType $type, User $scannedBy, CarbonInterface $at, ?RefusalReason $reason = null): AccessLog
    {
        return AccessLog::create([
            'client_id' => $client?->id,
            'type' => $type,
            'refusal_reason' => $reason,
            'scanned_at' => $at,
            'scanned_by' => $scannedBy->id,
        ]);
    }
}
