<?php

namespace Tests\Feature;

use App\Enums\EndReason;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionNotAllowedException;
use App\Models\BillingPeriod;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SubscriptionService
    {
        return app(SubscriptionService::class);
    }

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    public function test_first_payment_activates_pending_subscription(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $subscription = Subscription::factory()->pending()->create();

        $payment = $this->service()->recordPayment($subscription, $this->staff());

        $subscription->refresh();
        $this->assertSame('2027-01-10', $subscription->starts_on->toDateString());
        $this->assertSame('2027-02-10', $subscription->paid_until->toDateString());
        $this->assertSame(SubscriptionStatus::Active, $subscription->status());
        $this->assertSame(PaymentType::Period, $payment->type);
        $this->assertSame('2027-01-10', $payment->period_start->toDateString());
        $this->assertSame('2027-02-10', $payment->period_end->toDateString());
        $this->assertSame('30.00', $payment->amount);
    }

    public function test_due_date_counts_from_start_not_from_payment_day(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $subscription = Subscription::factory()->pending()->create();
        $this->service()->recordPayment($subscription, $this->staff());
        $this->travelTo('2027-02-15 10:00');

        $payment = $this->service()->recordPayment($subscription, $this->staff());

        $this->assertSame('2027-02-10', $payment->period_start->toDateString());
        $this->assertSame('2027-03-10', $subscription->refresh()->paid_until->toDateString());
    }

    public function test_month_end_start_does_not_drift(): void
    {
        $this->travelTo('2027-01-31 10:00');
        $subscription = Subscription::factory()->pending()->create();
        $this->service()->recordPayment($subscription, $this->staff());
        $this->assertSame('2027-02-28', $subscription->refresh()->paid_until->toDateString());

        $this->service()->recordPayment($subscription, $this->staff());

        $this->assertSame('2027-03-31', $subscription->refresh()->paid_until->toDateString());
    }

    public function test_status_transitions(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create();

        $this->travelTo('2027-02-10 12:00');
        $this->assertSame(SubscriptionStatus::Active, $subscription->status());
        $this->travelTo('2027-02-11 08:00');
        $this->assertSame(SubscriptionStatus::Overdue, $subscription->status());
        $this->travelTo('2027-02-12 08:00');
        $this->assertSame(SubscriptionStatus::Overdue, $subscription->status());
        $this->travelTo('2027-03-20 08:00');
        $this->assertSame(SubscriptionStatus::Overdue, $subscription->status());
    }

    public function test_amount_applies_period_discount(): void
    {
        $quarterly = BillingPeriod::factory()->create(['name' => 'Trimestral', 'months' => 3, 'discount_percent' => 5]);
        $yearly = BillingPeriod::factory()->create(['name' => 'Anual', 'months' => 12, 'discount_percent' => 10]);

        $a = Subscription::factory()->pending()->create(['billing_period_id' => $quarterly->id, 'discount_percent' => 5]);
        $b = Subscription::factory()->pending()->create(['billing_period_id' => $yearly->id, 'discount_percent' => 10]);

        $this->assertSame(85.5, $a->amountPerPeriod());
        $this->assertSame(324.0, $b->amountPerPeriod());
    }

    public function test_plan_price_change_does_not_affect_existing_subscription(): void
    {
        $subscription = Subscription::factory()->pending()->create(['monthly_price' => 30]);

        $subscription->plan->update(['monthly_price' => 99]);

        $this->assertSame(30.0, $subscription->fresh()->amountPerPeriod());
    }

    public function test_cannot_pay_ended_subscription(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create([
            'ended_on' => '2027-03-01',
            'end_reason' => EndReason::Overdue,
        ]);

        $this->expectException(SubscriptionNotAllowedException::class);

        $this->service()->recordPayment($subscription, $this->staff());
    }

    public function test_settle_debt_records_debt_payment(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create([
            'ended_on' => '2027-03-01',
            'end_reason' => EndReason::Overdue,
        ]);
        $this->assertTrue($subscription->hasOutstandingDebt());

        $payment = $this->service()->settleDebt($subscription, $this->staff());

        $this->assertSame(PaymentType::Debt, $payment->type);
        $this->assertSame('30.00', $payment->amount);
        $this->assertNull($payment->period_start);
        $this->assertNull($payment->period_end);
        $this->assertNotNull($subscription->refresh()->debt_settled_at);
        $this->assertFalse($subscription->hasOutstandingDebt());
    }

    public function test_cannot_settle_without_debt(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create();

        $this->expectException(SubscriptionNotAllowedException::class);

        $this->service()->settleDebt($subscription, $this->staff());
    }

    public function test_cancel_only_pending(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $pending = Subscription::factory()->pending()->create();

        $this->service()->cancel($pending);

        $pending->refresh();
        $this->assertSame('2027-01-10', $pending->ended_on->toDateString());
        $this->assertSame(EndReason::Cancelled, $pending->end_reason);
        $this->assertSame(SubscriptionStatus::Ended, $pending->status());

        $active = Subscription::factory()->activeFrom('2027-01-10')->create();
        $this->expectException(SubscriptionNotAllowedException::class);
        $this->service()->cancel($active);
    }
}
