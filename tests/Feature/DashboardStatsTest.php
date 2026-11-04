<?php

namespace Tests\Feature;

use App\Enums\AccessType;
use App\Enums\ChangeType;
use App\Enums\PaymentType;
use App\Models\AccessLog;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-04 12:00');
    }

    private function log(Client $client, AccessType $type, string $at): void
    {
        AccessLog::factory()->create(['client_id' => $client->id, 'type' => $type, 'scanned_at' => $at]);
    }

    private function stats(string $from, string $to): DashboardStats
    {
        return new DashboardStats(Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay());
    }

    public function test_counts(): void
    {
        Subscription::factory()->count(2)->activeFrom('2026-11-01')->create();
        Subscription::factory()->activeFrom('2026-08-01')->create();
        Subscription::factory()->pending()->create();
        $inside = Client::factory()->create();
        $left = Client::factory()->create();
        $this->log($left, AccessType::Entry, '2026-11-04 08:00');
        $this->log($left, AccessType::Exit, '2026-11-04 09:00');
        $this->log($inside, AccessType::Entry, '2026-11-04 10:00');
        $this->log($inside, AccessType::Refused, '2026-11-04 10:30');
        $this->log($left, AccessType::Refused, '2026-11-03 10:30');

        $this->assertSame(
            ['active' => 2, 'overdue' => 1, 'pending' => 1, 'inside' => 1, 'refused' => 2],
            (new DashboardStats)->counts(),
        );
    }

    public function test_clients_per_plan_includes_empty_plans(): void
    {
        $morning = Plan::factory()->create(['name' => 'Manhã']);
        Plan::factory()->create(['name' => 'Total']);
        Subscription::factory()->count(2)->activeFrom('2026-11-01')->create(['plan_id' => $morning->id]);
        Subscription::factory()->activeFrom('2026-08-01')->create(['plan_id' => $morning->id]);

        $this->assertSame(['Manhã' => 2, 'Total' => 0], (new DashboardStats)->clientsPerPlan());
    }

    public function test_plan_changes_totals_and_matrix(): void
    {
        $morning = Plan::factory()->create(['name' => 'Manhã']);
        $total = Plan::factory()->create(['name' => 'Total']);
        $old = Subscription::factory()->activeFrom('2026-10-01')->create(['plan_id' => $morning->id]);
        Subscription::factory()->activeFrom('2026-11-01')->create([
            'plan_id' => $total->id, 'change_type' => ChangeType::Upgrade, 'previous_subscription_id' => $old->id,
        ]);
        $old->payments()->create([
            'amount' => 30, 'type' => PaymentType::Period, 'paid_at' => '2026-11-02',
            'period_start' => '2026-11-01', 'period_end' => '2026-12-01', 'recorded_by' => User::factory()->staff()->create()->id,
        ]);

        $changes = (new DashboardStats)->planChanges();

        $this->assertSame(['upgrade' => 1, 'downgrade' => 0, 'lateral' => 0, 'renewal' => 1], $changes['totals']);
        $this->assertSame(1, $changes['matrix']['Manhã']['Total']);
    }

    public function test_peak_hours_average(): void
    {
        foreach ([Client::factory()->create(), Client::factory()->create()] as $client) {
            $this->log($client, AccessType::Entry, '2026-11-09 18:10');
            $this->log($client, AccessType::Exit, '2026-11-09 19:20');
        }

        $hours = $this->stats('2026-11-02', '2026-11-15')->peakHours();

        $this->assertSame(1.0, $hours[1][18]);
        $this->assertSame(1.0, $hours[1][19]);
        $this->assertSame(0.0, $hours[1][20]);
        $this->assertSame(0.0, $hours[2][18]);
        $this->assertCount(7, $hours);
        $this->assertCount(24, $hours[7]);
    }

    public function test_peak_hours_missing_exit_assumes_two_hours(): void
    {
        $this->log(Client::factory()->create(), AccessType::Entry, '2026-11-02 18:10');

        $hours = $this->stats('2026-11-02', '2026-11-02')->peakHours();

        $this->assertSame(1.0, $hours[1][18]);
        $this->assertSame(1.0, $hours[1][19]);
        $this->assertSame(1.0, $hours[1][20]);
        $this->assertSame(0.0, $hours[1][21]);
    }

    public function test_dashboard_page_renders(): void
    {
        Plan::factory()->create(['name' => 'Manhã']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('crm.dashboard'))->assertOk()->assertSee('Manhã')->assertSee('Seg');

        $this->get(route('crm.dashboard', ['from' => '2026-11-10', 'to' => '2026-11-01']))
            ->assertSessionHasErrors('to');
    }
}
