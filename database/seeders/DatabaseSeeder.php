<?php

namespace Database\Seeders;

use App\Models\BillingPeriod;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@gymflow.test',
        ]);

        $plans = [
            ['Manhã', 30, [['06:00', '14:00']]],
            ['Tarde', 30, [['14:00', '23:00']]],
            ['Parcial', 35, [['06:00', '08:00'], ['10:00', '17:00'], ['21:00', '23:00']]],
            ['Total', 45, [['00:00', '23:59']]],
        ];

        foreach ($plans as [$name, $price, $windows]) {
            $plan = Plan::create(['name' => $name, 'monthly_price' => $price]);

            foreach ($windows as [$from, $to]) {
                $plan->timeWindows()->create(['starts_at' => $from, 'ends_at' => $to]);
            }
        }

        foreach ([['Mensal', 1, 0], ['Trimestral', 3, 5], ['Semestral', 6, 7], ['Anual', 12, 10]] as [$name, $months, $discount]) {
            BillingPeriod::create(['name' => $name, 'months' => $months, 'discount_percent' => $discount]);
        }
    }
}
