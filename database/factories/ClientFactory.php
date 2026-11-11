<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->numerify('9########'),
            'nif' => fake()->unique()->numerify('2########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'qr_token' => Str::random(64),
            'active' => true,
        ];
    }
}
