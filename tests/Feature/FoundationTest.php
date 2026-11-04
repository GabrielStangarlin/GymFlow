<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\BillingPeriod;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_plans_and_billing_periods(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(Role::Admin, User::where('email', 'admin@gymflow.test')->first()->role);

        $plans = Plan::with('timeWindows')->get()->keyBy('name');
        $this->assertEqualsCanonicalizing(['Manhã', 'Tarde', 'Parcial', 'Total'], $plans->keys()->all());
        $this->assertEquals($plans['Manhã']->monthly_price, $plans['Tarde']->monthly_price);
        $this->assertTrue($plans['Tarde']->monthly_price < $plans['Parcial']->monthly_price);
        $this->assertTrue($plans['Parcial']->monthly_price < $plans['Total']->monthly_price);

        $total = $plans['Total']->timeWindows;
        $this->assertCount(1, $total);
        $this->assertSame('00:00', substr($total[0]->starts_at, 0, 5));
        $this->assertSame('23:59', substr($total[0]->ends_at, 0, 5));
        $this->assertGreaterThanOrEqual(2, $plans['Parcial']->timeWindows->count());

        $periods = BillingPeriod::orderBy('months')->get()
            ->map(fn (BillingPeriod $period) => [$period->name, $period->months, (float) $period->discount_percent])
            ->all();
        $this->assertSame([['Mensal', 1, 0.0], ['Trimestral', 3, 5.0], ['Semestral', 6, 7.0], ['Anual', 12, 10.0]], $periods);
    }

    public function test_enum_labels_are_portuguese(): void
    {
        $this->assertSame('Pendente', SubscriptionStatus::Pending->label());
        $this->assertSame('Ativa', SubscriptionStatus::Active->label());
        $this->assertSame('Em atraso', SubscriptionStatus::Overdue->label());
        $this->assertSame('Terminada', SubscriptionStatus::Ended->label());
    }
}
