<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_logs_in_and_lands_on_crm(): void
    {
        $staff = User::factory()->staff()->create();

        $this->post(route('login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('crm.dashboard'));
    }

    public function test_client_logs_in_and_lands_on_qr_page(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['user_id' => $user->id]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('client.qr'));
    }

    public function test_client_cannot_open_crm(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('crm.dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->staff()->inactive()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_with_open_session_is_logged_out(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        $staff->update(['active' => false]);

        $this->get(route('crm.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_link_only_visible_to_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('crm.dashboard'))
            ->assertSee('Funcionários');

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('crm.dashboard'))
            ->assertDontSee('Funcionários');
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), ['email' => 'x@example.com', 'password' => 'wrong'])->assertStatus(302);
        }

        $this->post(route('login'), ['email' => 'x@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
