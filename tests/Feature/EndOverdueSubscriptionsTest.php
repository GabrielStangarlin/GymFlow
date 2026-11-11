<?php

namespace Tests\Feature;

use App\Enums\EndReason;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndOverdueSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_two_days_overdue_is_not_ended(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create();
        $this->travelTo('2027-02-12 10:00');

        $this->artisan('gymflow:end-overdue')->assertSuccessful();

        $this->assertNull($subscription->fresh()->ended_on);
    }

    public function test_subscription_three_days_overdue_is_ended_with_debt(): void
    {
        $subscription = Subscription::factory()->activeFrom('2027-01-10')->create();
        $this->travelTo('2027-02-13 10:00');

        $this->artisan('gymflow:end-overdue')->assertSuccessful();

        $subscription->refresh();
        $this->assertSame('2027-02-13', $subscription->ended_on->toDateString());
        $this->assertSame(EndReason::Overdue, $subscription->end_reason);
        $this->assertTrue($subscription->hasOutstandingDebt());
    }

    public function test_pending_subscriptions_are_never_ended(): void
    {
        $subscription = Subscription::factory()->pending()->create();
        $this->travelTo('2030-01-01 10:00');

        $this->artisan('gymflow:end-overdue')->assertSuccessful();

        $this->assertNull($subscription->fresh()->ended_on);
    }

    public function test_command_reports_count_and_ignores_ended_and_paid_up_subscriptions(): void
    {
        $this->travelTo('2027-02-13 10:00');
        $overdue = Subscription::factory()->count(2)->activeFrom('2027-01-10')->create();
        $paidUp = Subscription::factory()->activeFrom('2027-02-01')->create();
        $alreadyEnded = Subscription::factory()->activeFrom('2027-01-10')->create(['ended_on' => '2027-02-11', 'end_reason' => EndReason::Overdue]);

        $this->artisan('gymflow:end-overdue')
            ->expectsOutput('Assinaturas terminadas: 2')
            ->assertSuccessful();

        $this->assertNull($paidUp->fresh()->ended_on);
        $this->assertSame('2027-02-11', $alreadyEnded->fresh()->ended_on->toDateString());
        $overdue->each(fn (Subscription $subscription) => $this->assertSame('2027-02-13', $subscription->fresh()->ended_on->toDateString()));
    }
}
