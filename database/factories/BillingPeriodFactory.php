<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mensal',
            'months' => 1,
            'discount_percent' => 0,
        ];
    }
}
