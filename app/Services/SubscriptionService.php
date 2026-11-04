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
        return DB::transaction(function () use ($subscription, $recordedBy): Payment {
            $locked = $this->lock($subscription);

            if ($locked->status() === SubscriptionStatus::Ended) {
                throw new SubscriptionNotAllowedException('Não é possível registar pagamentos numa assinatura terminada.');
            }

            $isFirstPayment = $locked->paid_until === null;
            $periodStart = $isFirstPayment ? today() : $locked->paid_until->copy();
            $startsOn = $isFirstPayment ? $periodStart : $locked->starts_on;

            $paymentsCount = $locked->payments()->where('type', PaymentType::Period)->count() + 1;
            $periodEnd = $startsOn->copy()->addMonthsNoOverflow($locked->billingPeriod->months * $paymentsCount);

            $payment = $locked->payments()->create([
                'amount' => $locked->amountPerPeriod(),
                'type' => PaymentType::Period,
                'paid_at' => now(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'recorded_by' => $recordedBy->id,
            ]);

            $locked->update(['starts_on' => $startsOn, 'paid_until' => $periodEnd]);
            $subscription->refresh();

            return $payment;
        });
    }

    /**
     * @throws SubscriptionNotAllowedException
     */
    public function settleDebt(Subscription $subscription, User $recordedBy): Payment
    {
        return DB::transaction(function () use ($subscription, $recordedBy): Payment {
            $locked = $this->lock($subscription);

            if (! $locked->hasOutstandingDebt()) {
                throw new SubscriptionNotAllowedException('Esta assinatura não tem dívida por regularizar.');
            }

            $payment = $locked->payments()->create([
                'amount' => $locked->amountPerPeriod(),
                'type' => PaymentType::Debt,
                'paid_at' => now(),
                'recorded_by' => $recordedBy->id,
            ]);

            $locked->update(['debt_settled_at' => now()]);
            $subscription->refresh();

            return $payment;
        });
    }

    /**
     * @throws SubscriptionNotAllowedException
     */
    public function cancel(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription): void {
            $locked = $this->lock($subscription);

            if ($locked->status() !== SubscriptionStatus::Pending) {
                throw new SubscriptionNotAllowedException('Só é possível cancelar assinaturas pendentes.');
            }

            $locked->update(['ended_on' => today(), 'end_reason' => EndReason::Cancelled]);
            $subscription->refresh();
        });
    }

    private function lock(Subscription $subscription): Subscription
    {
        return Subscription::lockForUpdate()->findOrFail($subscription->id);
    }
}
