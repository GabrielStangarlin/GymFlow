<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'amount' => 30,
            'type' => PaymentType::Period,
            'paid_at' => now(),
            'recorded_by' => User::factory()->staff(),
        ];
    }
}
