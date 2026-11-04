<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_page_renders_for_staff(): void
    {
        $response = $this->actingAs(User::factory()->staff()->create())->get(route('crm.scan.show'));

        $response->assertOk()
            ->assertSee('data-scan-url="'.route('crm.scan.store').'"', false)
            ->assertSee('Ler QR');
    }
}
