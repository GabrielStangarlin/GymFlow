<?php

namespace Database\Factories;

use App\Enums\ChangeType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'plan_id' => Plan::factory(),
            'billing_period_id' => BillingPeriod::factory(),
            'payment_method' => PaymentMethod::Cash,
            'monthly_price' => 30,
            'discount_percent' => 0,
            'change_type' => ChangeType::New,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_on' => null,
            'paid_until' => null,
        ]);
    }

    public function activeFrom(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_on' => $date,
            'paid_until' => Carbon::parse($date)->addMonthNoOverflow()->toDateString(),
        ])->afterCreating(function (Subscription $subscription): void {
            $subscription->update(['paid_until' => Carbon::parse($subscription->starts_on)->addMonthsNoOverflow($subscription->billingPeriod->months)->toDateString()]);
            $subscription->payments()->create([
                'amount' => $subscription->amountPerPeriod(),
                'type' => PaymentType::Period,
                'paid_at' => $subscription->starts_on,
                'period_start' => $subscription->starts_on,
                'period_end' => $subscription->paid_until,
                'recorded_by' => User::factory()->staff()->create()->id,
            ]);
        });
    }
}
