<?php

namespace App\Services;

use App\Enums\ChangeType;
use App\Enums\EndReason;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionNotAllowedException;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * @throws SubscriptionNotAllowedException
     */
    public function create(
        Client $client,
        Plan $plan,
        BillingPeriod $billingPeriod,
        PaymentMethod $paymentMethod,
        bool $paidNow,
        User $recordedBy,
    ): Subscription {
        $this->endIfPastGrace($client->currentSubscription());

        return DB::transaction(function () use ($client, $plan, $billingPeriod, $paymentMethod, $paidNow, $recordedBy): Subscription {
            $this->lockClient($client);
            $current = $client->currentSubscription();

            if ($client->hasOutstandingDebt()) {
                throw new SubscriptionNotAllowedException('O aluno tem uma dívida por regularizar.');
            }

            if ($current !== null) {
                match ($current->status()) {
                    SubscriptionStatus::Active => throw new SubscriptionNotAllowedException('A assinatura atual ainda não terminou.'),
                    SubscriptionStatus::Pending => throw new SubscriptionNotAllowedException('Cancele primeiro a assinatura pendente.'),
                    default => $current->update(['ended_on' => today(), 'end_reason' => EndReason::Replaced]),
                };
            }

            $previous = $client->subscriptions()->whereNotNull('starts_on')->latest('id')->with('plan')->first();

            $subscription = $client->subscriptions()->create([
                'plan_id' => $plan->id,
                'billing_period_id' => $billingPeriod->id,
                'payment_method' => $paymentMethod,
                'monthly_price' => $plan->monthly_price,
                'discount_percent' => $billingPeriod->discount_percent,
                'change_type' => $this->classifyChange($previous, $plan),
                'previous_subscription_id' => $previous?->id,
            ]);

            if ($paidNow) {
                $this->recordPayment($subscription, $recordedBy);
            }

            return $subscription;
        });
    }

    /**
     * The daily command may not have run yet. Committed on its own so the debt survives a later exception.
     */
    public function endIfPastGrace(?Subscription $subscription): bool
    {
        if ($subscription?->paid_until === null) {
            return false;
        }

        return DB::transaction(function () use ($subscription): bool {
            $this->lockClient($subscription->client);
            $locked = $this->lock($subscription);

            if ($locked->ended_on !== null || $locked->paid_until->toDateString() >= today()->subDays(2)->toDateString()) {
                return false;
            }

            $this->endAsOverdue($locked, today());
            $subscription->refresh();

            return true;
        });
    }

    public function endAsOverdue(Subscription $subscription, CarbonInterface $today): void
    {
        $subscription->update(['ended_on' => $today, 'end_reason' => EndReason::Overdue]);
    }

    public function endOverdue(CarbonInterface $today): int
    {
        $ended = 0;

        $cutoff = $today->copy()->subDays(2)->toDateString();

        Subscription::whereNull('ended_on')
            ->whereNotNull('paid_until')
            ->whereDate('paid_until', '<', $cutoff)
            ->lazyById()
            ->each(function (Subscription $subscription) use ($today, $cutoff, &$ended): void {
                DB::transaction(function () use ($subscription, $today, $cutoff, &$ended): void {
                    $locked = $this->lock($subscription);

                    if ($locked->ended_on === null && $locked->paid_until?->toDateString() < $cutoff) {
                        $this->endAsOverdue($locked, $today);
                        $ended++;
                    }
                });
            });

        return $ended;
    }

    private function classifyChange(?Subscription $previous, Plan $plan): ChangeType
    {
        if ($previous === null) {
            return ChangeType::New;
        }

        if ($previous->plan_id === $plan->id) {
            return ChangeType::Renewal;
        }

        return match (bccomp((string) $plan->monthly_price, (string) $previous->plan->monthly_price, 2)) {
            1 => ChangeType::Upgrade,
            -1 => ChangeType::Downgrade,
            default => ChangeType::Lateral,
        };
    }

    /**
     * @throws SubscriptionNotAllowedException
     */
    public function recordPayment(Subscription $subscription, User $recordedBy): Payment
    {
        if ($this->endIfPastGrace($subscription)) {
            throw new SubscriptionNotAllowedException('A assinatura terminou por falta de pagamento. Regularize a dívida e crie uma nova assinatura.');
        }

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

    private function lockClient(Client $client): void
    {
        Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
    }

    private function lock(Subscription $subscription): Subscription
    {
        return Subscription::lockForUpdate()->findOrFail($subscription->id);
    }
}
