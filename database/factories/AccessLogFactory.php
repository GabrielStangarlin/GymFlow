<?php

namespace Database\Factories;

use App\Enums\AccessType;
use App\Models\AccessLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessLog>
 */
class AccessLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'type' => AccessType::Entry,
            'scanned_at' => now(),
            'scanned_by' => User::factory()->staff(),
        ];
    }
}
