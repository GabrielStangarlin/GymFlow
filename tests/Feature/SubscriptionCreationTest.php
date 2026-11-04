<?php

namespace Tests\Feature;

use App\Enums\ChangeType;
use App\Enums\EndReason;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionNotAllowedException;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCreationTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(Client $client, Plan $plan, bool $paidNow = false): Subscription
    {
        return app(SubscriptionService::class)->create(
            $client,
            $plan,
            BillingPeriod::factory()->create(),
            PaymentMethod::Cash,
            $paidNow,
            User::factory()->staff()->create(),
        );
    }

    public function test_first_subscription_is_new(): void
    {
        $client = Client::factory()->create();

        $subscription = $this->subscribe($client, Plan::factory()->create());

        $this->assertSame(ChangeType::New, $subscription->change_type);
        $this->assertSame('30.00', $subscription->monthly_price);
        $this->assertSame('0.00', $subscription->discount_percent);
        $this->assertNull($subscription->previous_subscription_id);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->status());
    }

    public function test_upgrade_downgrade_lateral_and_renewal(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $client = Client::factory()->create();
        $morning = Plan::factory()->create(['monthly_price' => 30]);
        $total = Plan::factory()->create(['monthly_price' => 50]);
        $partial = Plan::factory()->create(['monthly_price' => 20]);
        $afternoon = Plan::factory()->create(['monthly_price' => 20]);

        $expected = [
            [$morning, ChangeType::New],
            [$total, ChangeType::Upgrade],
            [$partial, ChangeType::Downgrade],
            [$afternoon, ChangeType::Lateral],
            [$afternoon, ChangeType::Renewal],
        ];

        foreach ($expected as [$plan, $type]) {
            $subscription = $this->subscribe($client, $plan, true);
            $this->assertSame($type, $subscription->change_type);
            $this->travelTo(today()->addMonth()->addDay());
        }
    }

    public function test_cannot_create_while_current_is_active(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $client = Client::factory()->create();
        Subscription::factory()->activeFrom('2027-01-05')->create(['client_id' => $client->id]);

        $this->expectException(SubscriptionNotAllowedException::class);
        $this->expectExceptionMessage('A assinatura atual ainda não terminou.');

        $this->subscribe($client, Plan::factory()->create());
    }

    public function test_cannot_create_while_pending(): void
    {
        $client = Client::factory()->create();
        Subscription::factory()->pending()->create(['client_id' => $client->id]);

        $this->expectException(SubscriptionNotAllowedException::class);
        $this->expectExceptionMessage('Cancele primeiro a assinatura pendente.');

        $this->subscribe($client, Plan::factory()->create());
    }

    public function test_overdue_current_is_replaced_without_debt(): void
    {
        $this->travelTo('2027-02-12 10:00');
        $client = Client::factory()->create();
        $old = Subscription::factory()->activeFrom('2027-01-10')->create(['client_id' => $client->id]);

        $new = $this->subscribe($client, Plan::factory()->create());

        $old->refresh();
        $this->assertSame(EndReason::Replaced, $old->end_reason);
        $this->assertFalse($old->hasOutstandingDebt());
        $this->assertSame($old->id, $new->previous_subscription_id);
    }

    public function test_cannot_create_with_outstanding_debt(): void
    {
        $client = Client::factory()->create();
        Subscription::factory()->activeFrom('2027-01-10')->create([
            'client_id' => $client->id,
            'ended_on' => '2027-02-15',
            'end_reason' => EndReason::Overdue,
        ]);

        $this->expectException(SubscriptionNotAllowedException::class);
        $this->expectExceptionMessage('O aluno tem uma dívida por regularizar.');

        $this->subscribe($client, Plan::factory()->create());
    }

    public function test_overdue_beyond_two_days_blocks_even_if_command_did_not_run(): void
    {
        $this->travelTo('2027-02-15 10:00');
        $client = Client::factory()->create();
        $old = Subscription::factory()->activeFrom('2027-01-10')->create(['client_id' => $client->id]);

        try {
            $this->subscribe($client, Plan::factory()->create());
            $this->fail('Expected exception');
        } catch (SubscriptionNotAllowedException $e) {
            $this->assertSame('O aluno tem uma dívida por regularizar.', $e->getMessage());
        }

        $this->assertTrue($old->refresh()->hasOutstandingDebt());
    }

    public function test_paid_now_activates_immediately(): void
    {
        $this->travelTo('2027-01-10 10:00');

        $subscription = $this->subscribe(Client::factory()->create(), Plan::factory()->create(), true);

        $this->assertSame(SubscriptionStatus::Active, $subscription->status());
        $this->assertSame('2027-02-10', $subscription->paid_until->toDateString());
    }

    public function test_cancelled_pending_is_not_used_as_previous(): void
    {
        $client = Client::factory()->create();
        Subscription::factory()->pending()->create([
            'client_id' => $client->id,
            'ended_on' => today(),
            'end_reason' => EndReason::Cancelled,
        ]);

        $subscription = $this->subscribe($client, Plan::factory()->create());

        $this->assertSame(ChangeType::New, $subscription->change_type);
        $this->assertNull($subscription->previous_subscription_id);
    }

    public function test_classification_uses_current_plan_prices(): void
    {
        $this->travelTo('2027-01-10 10:00');
        $client = Client::factory()->create();
        $old = Plan::factory()->create(['monthly_price' => 30]);
        $this->subscribe($client, $old, true);
        $old->update(['monthly_price' => 60]);
        $this->travelTo('2027-02-11 10:00');

        $subscription = $this->subscribe($client, Plan::factory()->create(['monthly_price' => 50]));

        $this->assertSame(ChangeType::Downgrade, $subscription->change_type);
    }
}
