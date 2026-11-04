<?php

namespace App\Services;

use App\Enums\EndReason;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionNotAllowedException;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * @throws SubscriptionNotAllowedException
     */
    public function recordPayment(Subscription $subscription, User $recordedBy): Payment
    {
        if ($subscription->status() === SubscriptionStatus::Ended) {
            throw new SubscriptionNotAllowedException('Não é possível registar pagamentos numa assinatura terminada.');
        }

        return DB::transaction(function () use ($subscription, $recordedBy): Payment {
            $isFirstPayment = $subscription->paid_until === null;
            $periodStart = $isFirstPayment ? today() : $subscription->paid_until->copy();
            $startsOn = $isFirstPayment ? $periodStart : $subscription->starts_on;

            $paymentsCount = $subscription->payments()->where('type', PaymentType::Period)->count() + 1;
            $periodEnd = $startsOn->copy()->addMonthsNoOverflow($subscription->billingPeriod->months * $paymentsCount);

            $payment = $subscription->payments()->create([
                'amount' => $subscription->amountPerPeriod(),
                'type' => PaymentType::Period,
                'paid_at' => now(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'recorded_by' => $recordedBy->id,
            ]);

            $subscription->update(['starts_on' => $startsOn, 'paid_until' => $periodEnd]);

            return $payment;
        });
    }

    /**
     * @throws SubscriptionNotAllowedException
     */
    public function settleDebt(Subscription $subscription, User $recordedBy): Payment
    {
        if (! $subscription->hasOutstandingDebt()) {
            throw new SubscriptionNotAllowedException('Esta assinatura não tem dívida por regularizar.');
        }

        return DB::transaction(function () use ($subscription, $recordedBy): Payment {
            $payment = $subscription->payments()->create([
                'amount' => $subscription->amountPerPeriod(),
                'type' => PaymentType::Debt,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);

            $subscription->update(['debt_settled_at' => now()]);

            return $payment;
        });
    }

    /**
     * @throws SubscriptionNotAllowedException
     */
    public function cancel(Subscription $subscription): void
    {
        if ($subscription->status() !== SubscriptionStatus::Pending) {
            throw new SubscriptionNotAllowedException('Só é possível cancelar assinaturas pendentes.');
        }

        $subscription->update(['ended_on' => today(), 'end_reason' => EndReason::Cancelled]);
    }
}
