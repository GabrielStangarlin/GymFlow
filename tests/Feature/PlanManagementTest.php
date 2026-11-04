<?php

namespace Tests\Feature;

use App\Models\BillingPeriod;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_plan_with_windows(): void
    {
        $this->actingAs(User::factory()->staff()->create())->post(route('crm.plans.store'), [
            'name' => 'Manhã',
            'monthly_price' => '30.00',
            'active' => '1',
            'windows' => [
                ['starts_at' => '06:00', 'ends_at' => '12:00'],
                ['starts_at' => '14:00', 'ends_at' => '16:00'],
            ],
        ])->assertRedirect(route('crm.plans.index'));

        $plan = Plan::where('name', 'Manhã')->firstOrFail();
        $this->assertTrue($plan->active);
        $this->assertCount(2, $plan->timeWindows);
    }

    public function test_update_plan_replaces_windows(): void
    {
        $plan = Plan::factory()->withWindow('06:00', '14:00')->create();

        $this->actingAs(User::factory()->staff()->create())->put(route('crm.plans.update', $plan), [
            'name' => 'Tarde',
            'monthly_price' => '35',
            'windows' => [['starts_at' => '14:00', 'ends_at' => '22:00']],
        ])->assertRedirect(route('crm.plans.index'));

        $plan->refresh()->load('timeWindows');
        $this->assertSame('Tarde', $plan->name);
        $this->assertFalse($plan->active);
        $this->assertCount(1, $plan->timeWindows);
        $this->assertSame('14:00', substr($plan->timeWindows[0]->starts_at, 0, 5));
    }

    public function test_window_end_must_be_after_start(): void
    {
        $this->actingAs(User::factory()->staff()->create())->post(route('crm.plans.store'), [
            'name' => 'Mau',
            'monthly_price' => '30',
            'windows' => [['starts_at' => '14:00', 'ends_at' => '12:00']],
        ])->assertSessionHasErrors('windows.0.ends_at');

        $this->assertDatabaseCount('plans', 0);
    }

    public function test_inactive_plan_not_offered_on_enrollment(): void
    {
        Plan::factory()->create(['name' => 'Visivel']);
        Plan::factory()->create(['name' => 'Escondido', 'active' => false]);

        $this->actingAs(User::factory()->staff()->create())->get(route('crm.enrollments.create'))
            ->assertSee('Visivel')
            ->assertDontSee('Escondido');
    }

    public function test_update_billing_period_discounts(): void
    {
        $period = BillingPeriod::factory()->create(['name' => 'Trimestral', 'months' => 3, 'discount_percent' => 5]);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('crm.billing-periods.update'), ['discounts' => [$period->id => '8.5']])
            ->assertRedirect(route('crm.billing-periods.edit'));

        $this->assertSame('8.50', $period->refresh()->discount_percent);
        $this->actingAs(User::factory()->staff()->create())
            ->put(route('crm.billing-periods.update'), ['discounts' => [$period->id => '120']])
            ->assertSessionHasErrors("discounts.{$period->id}");
    }
}
