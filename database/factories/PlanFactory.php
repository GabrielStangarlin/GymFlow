<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'monthly_price' => 30,
            'active' => true,
        ];
    }

    public function withWindow(string $from, string $to): static
    {
        return $this->afterCreating(fn (Plan $plan) => $plan->timeWindows()->create([
            'starts_at' => $from,
            'ends_at' => $to,
        ]));
    }
}
