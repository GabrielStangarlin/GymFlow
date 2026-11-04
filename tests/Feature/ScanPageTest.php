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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('crm.scan.show'))->assertRedirect(route('login'));
    }

    public function test_client_cannot_see_scan_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('crm.scan.show'))->assertForbidden();
    }
}
