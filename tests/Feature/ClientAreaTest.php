<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_own_qr_code(): void
    {
        $user = User::factory()->create(['name' => 'Maria Aluna']);
        Client::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('client.qr'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('Maria Aluna');
    }

    public function test_client_user_without_client_record_gets_404(): void
    {
        $this->actingAs(User::factory()->create())->get(route('client.qr'))->assertNotFound();
    }

    public function test_qr_svg_encodes_token(): void
    {
        $client = Client::factory()->create(['qr_token' => 'token-abc']);
        $other = Client::factory()->create(['qr_token' => 'token-xyz']);

        $svg = $client->qrSvg();

        $this->assertMatchesRegularExpression('/^(<\?xml|<svg)/', $svg);
        $this->assertSame($svg, $client->qrSvg());
        $this->assertNotSame($svg, $other->qrSvg());
    }
}
