<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanTimeWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanTimeWindow>
 */
class PlanTimeWindowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'starts_at' => '06:00',
            'ends_at' => '14:00',
        ];
    }
}
