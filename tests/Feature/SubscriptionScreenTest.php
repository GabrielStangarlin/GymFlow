<?php

namespace Tests\Feature;

use App\Enums\EndReason;
use App\Enums\PaymentType;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionScreenTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    public function test_filter_by_status(): void
    {
        $this->travelTo('2027-03-15 10:00');
        $pending = Subscription::factory()->pending()->create();
        $active = Subscription::factory()->activeFrom('2027-03-01')->create();
        $overdue = Subscription::factory()->activeFrom('2027-01-01')->create();
        $ended = Subscription::factory()->pending()->create(['ended_on' => '2027-03-01', 'end_reason' => EndReason::Cancelled]);
        $debt = Subscription::factory()->activeFrom('2027-01-01')->create(['ended_on' => '2027-03-01', 'end_reason' => EndReason::Overdue]);

        $ids = fn (string $status) => $this->actingAs($this->staff())
            ->get(route('crm.subscriptions.index', ['status' => $status]))
            ->assertOk()
            ->viewData('subscriptions')->pluck('id')->sort()->values()->all();

        $this->assertSame([$pending->id], $ids('pending'));
        $this->assertSame([$active->id], $ids('active'));
        $this->assertSame([$overdue->id], $ids('overdue'));
        $this->assertSame([$ended->id, $debt->id], $ids('ended'));
        $this->assertSame([$debt->id], $ids('debt'));
        $this->assertCount(5, $ids(''));
    }

    public function test_mark_as_paid_extends_period(): void
    {
        $this->travelTo('2027-03-15 10:00');
        $subscription = Subscription::factory()->pending()->create();

        $this->actingAs($this->staff())
            ->post(route('crm.subscriptions.pay', $subscription))
            ->assertSessionHas('status', 'Pagamento registado.');

        $this->assertSame('2027-04-15', $subscription->refresh()->paid_until->toDateString());
    }

    public function test_settle_debt_action(): void
    {
        $subscription = Subscription::factory()->pending()->create(['ended_on' => today(), 'end_reason' => EndReason::Overdue]);

        $this->actingAs($this->staff())
            ->post(route('crm.subscriptions.settle-debt', $subscription))
            ->assertSessionHas('status', 'Dívida regularizada.');

        $this->assertNotNull($subscription->refresh()->debt_settled_at);
        $this->assertSame(1, $subscription->payments()->where('type', PaymentType::Debt)->count());
    }

    public function test_cancel_pending_action(): void
    {
        $subscription = Subscription::factory()->pending()->create();

        $this->actingAs($this->staff())
            ->post(route('crm.subscriptions.cancel', $subscription))
            ->assertSessionHas('status', 'Assinatura cancelada.');

        $this->assertSame(EndReason::Cancelled, $subscription->refresh()->end_reason);
    }

    public function test_pay_ended_shows_error(): void
    {
        $subscription = Subscription::factory()->pending()->create(['ended_on' => today(), 'end_reason' => EndReason::Cancelled]);

        $this->actingAs($this->staff())
            ->post(route('crm.subscriptions.pay', $subscription))
            ->assertSessionHasErrors('subscription');

        $this->assertNull($subscription->refresh()->paid_until);
    }
}
