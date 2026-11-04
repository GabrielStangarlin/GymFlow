<?php

namespace Tests\Feature;

use App\Enums\AccessType;
use App\Enums\RefusalReason;
use App\Models\AccessLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessLogScreenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $query
     * @return array<int, int>
     */
    private function ids(array $query): array
    {
        return $this->actingAs(User::factory()->staff()->create())
            ->get(route('crm.access-logs.index', $query))
            ->assertOk()
            ->viewData('logs')->pluck('id')->all();
    }

    public function test_filters_by_date_client_and_type(): void
    {
        $ana = Client::factory()->create(['user_id' => User::factory()->create(['name' => 'Ana Silva'])]);
        $rui = Client::factory()->create(['user_id' => User::factory()->create(['name' => 'Rui Costa'])]);
        $a = AccessLog::factory()->create(['client_id' => $ana->id, 'scanned_at' => '2027-03-10 09:00']);
        $b = AccessLog::factory()->create(['client_id' => $ana->id, 'type' => AccessType::Exit, 'scanned_at' => '2027-03-10 10:00']);
        $c = AccessLog::factory()->create(['client_id' => $rui->id, 'scanned_at' => '2027-03-11 09:00']);

        $this->assertSame([$c->id, $b->id, $a->id], $this->ids([]));
        $this->assertSame([$b->id, $a->id], $this->ids(['date' => '2027-03-10']));
        $this->assertSame([$b->id, $a->id], $this->ids(['client' => 'Ana']));
        $this->assertSame([$c->id], $this->ids(['client' => $rui->nif]));
        $this->assertSame([$b->id], $this->ids(['type' => 'exit']));
        $this->assertSame([$c->id, $b->id, $a->id], $this->ids(['type' => 'bogus']));
    }

    public function test_refused_rows_show_reason_label(): void
    {
        AccessLog::factory()->create(['client_id' => null, 'type' => AccessType::Refused, 'refusal_reason' => RefusalReason::Unknown]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('crm.access-logs.index'))
            ->assertOk()
            ->assertSee(RefusalReason::Unknown->label())
            ->assertSee('Desconhecido');
    }
}
