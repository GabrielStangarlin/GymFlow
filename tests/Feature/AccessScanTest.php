<?php

namespace Tests\Feature;

use App\Enums\AccessType;
use App\Enums\RefusalReason;
use App\Models\AccessLog;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessScanTest extends TestCase
{
    use RefreshDatabase;

    private function activeClient(string $from = '2027-01-01'): Client
    {
        $client = Client::factory()->create();
        Subscription::factory()->activeFrom($from)->create([
            'client_id' => $client->id,
            'plan_id' => Plan::factory()->withWindow('06:00', '14:00')->create()->id,
        ]);

        return $client;
    }

    private function scan(Client|string $client): AccessLog
    {
        $token = $client instanceof Client ? $client->qr_token : $client;

        return app(AccessService::class)->scan($token, User::factory()->staff()->create());
    }

    private function assertRefused(AccessLog $log, RefusalReason $reason): void
    {
        $this->assertSame(AccessType::Refused, $log->type);
        $this->assertSame($reason, $log->refusal_reason);
    }

    public function test_first_scan_of_day_is_entry(): void
    {
        $this->travelTo('2027-01-10 09:00');

        $this->assertSame(AccessType::Entry, $this->scan($this->activeClient())->type);
    }

    public function test_second_scan_is_exit(): void
    {
        $this->travelTo('2027-01-10 09:00');
        $client = $this->activeClient();
        $this->scan($client);
        $this->travelTo('2027-01-10 10:00');

        $this->assertSame(AccessType::Exit, $this->scan($client)->type);
    }

    public function test_third_scan_is_entry_again(): void
    {
        $this->travelTo('2027-01-10 09:00');
        $client = $this->activeClient();
        $this->scan($client);
        $this->travelTo('2027-01-10 10:00');
        $this->scan($client);
        $this->travelTo('2027-01-10 11:00');

        $this->assertSame(AccessType::Entry, $this->scan($client)->type);
    }

    public function test_exit_allowed_outside_plan_hours(): void
    {
        $this->travelTo('2027-01-10 13:50');
        $client = $this->activeClient();
        $this->scan($client);
        $this->travelTo('2027-01-10 14:30');

        $this->assertSame(AccessType::Exit, $this->scan($client)->type);
    }

    public function test_refused_when_pending(): void
    {
        $this->travelTo('2027-01-10 09:00');
        $client = Client::factory()->create();
        Subscription::factory()->pending()->create(['client_id' => $client->id]);

        $this->assertRefused($this->scan($client), RefusalReason::Pending);
    }

    public function test_refused_when_overdue(): void
    {
        $this->travelTo('2027-02-03 09:00');

        $this->assertRefused($this->scan($this->activeClient('2027-01-01')), RefusalReason::Overdue);
    }

    public function test_refused_outside_hours(): void
    {
        $this->travelTo('2027-01-10 18:00');

        $this->assertRefused($this->scan($this->activeClient()), RefusalReason::OutsideHours);
    }

    public function test_refused_without_subscription(): void
    {
        $this->travelTo('2027-01-10 09:00');

        $this->assertRefused($this->scan(Client::factory()->create()), RefusalReason::NoSubscription);
    }

    public function test_refused_unknown_token(): void
    {
        $log = $this->scan('nao-existe');

        $this->assertRefused($log, RefusalReason::Unknown);
        $this->assertNull($log->client_id);
    }

    public function test_refused_inactive_client(): void
    {
        $this->travelTo('2027-01-10 09:00');
        $client = $this->activeClient();
        $client->update(['active' => false]);

        $this->assertRefused($this->scan($client), RefusalReason::Unknown);
    }

    public function test_duplicate_scan_within_60_seconds_is_ignored(): void
    {
        $this->travelTo('2027-01-10 09:00:00');
        $client = $this->activeClient();
        $first = $this->scan($client);
        $this->travelTo('2027-01-10 09:00:30');

        $this->assertSame($first->id, $this->scan($client)->id);
        $this->assertSame(1, AccessLog::count());
    }

    public function test_missing_exit_yesterday_means_entry_today(): void
    {
        $this->travelTo('2027-01-10 13:00');
        $client = $this->activeClient();
        $this->scan($client);
        $this->travelTo('2027-01-11 09:00');

        $this->assertSame(AccessType::Entry, $this->scan($client)->type);
    }

    public function test_scan_endpoint_returns_json_labels(): void
    {
        $this->travelTo('2027-01-10 09:00');
        $client = $this->activeClient();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->postJson(route('crm.scan.store'), ['token' => $client->qr_token])
            ->assertOk()
            ->assertJsonPath('type', 'entry')
            ->assertJsonPath('type_label', 'Entrada')
            ->assertJsonPath('reason', null)
            ->assertJsonPath('client_name', $client->name);

        $this->travelTo('2027-01-10 18:00');
        $other = $this->activeClient();

        $this->actingAs($staff)->postJson(route('crm.scan.store'), ['token' => $other->qr_token])
            ->assertOk()
            ->assertJsonPath('type', 'refused')
            ->assertJsonPath('reason_label', 'Fora do horário do plano');
    }

    public function test_scan_endpoint_requires_token_and_staff(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->postJson(route('crm.scan.store'), [])->assertUnprocessable();
        $this->actingAs(User::factory()->create())
            ->postJson(route('crm.scan.store'), ['token' => 'x'])->assertForbidden();
    }
}
