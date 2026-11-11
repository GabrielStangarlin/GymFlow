<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\ActivateAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rui Costa',
            'email' => 'rui@example.com',
            'phone' => '912345678',
            'nif' => '123456789',
            'birth_date' => '1995-05-10',
            'plan_id' => Plan::factory()->create()->id,
            'billing_period_id' => BillingPeriod::factory()->create()->id,
            'payment_method' => PaymentMethod::Cash->value,
            'paid_now' => '0',
        ], $overrides);
    }

    public function test_staff_enrolls_client_and_activation_is_sent(): void
    {
        Notification::fake();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('crm.enrollments.store'), $this->payload())->assertSessionHasNoErrors();

        $user = User::where('email', 'rui@example.com')->firstOrFail();
        $this->assertSame(Role::Client, $user->role);
        $client = Client::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(SubscriptionStatus::Pending, $client->currentSubscription()->status());
        Notification::assertSentTo($user, ActivateAccount::class);
    }

    public function test_enroll_with_paid_now_creates_active_subscription(): void
    {
        Notification::fake();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('crm.enrollments.store'), $this->payload(['paid_now' => '1']));

        $client = Client::where('nif', '123456789')->firstOrFail();
        $this->assertSame(SubscriptionStatus::Active, $client->currentSubscription()->status());
    }

    public function test_duplicate_email_or_nif_is_rejected(): void
    {
        $staff = User::factory()->staff()->create();
        $existing = Client::factory()->create(['nif' => '999999999']);

        $this->actingAs($staff)->post(route('crm.enrollments.store'), $this->payload(['email' => $staff->email]))
            ->assertSessionHasErrors('email');
        $this->actingAs($staff)->post(route('crm.enrollments.store'), $this->payload(['nif' => '999999999']))
            ->assertSessionHasErrors('nif');

        $this->assertSame(1, Client::count());
        $this->assertNotNull($existing);
    }

    public function test_client_activates_account_and_sees_qr(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('activations')->createToken($user);

        $this->post(route('activation.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'novasenha123',
            'password_confirmation' => 'novasenha123',
        ])->assertRedirect(route('client.qr'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_activation_link_expires_after_48_hours(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('activations')->createToken($user);

        $this->travel(49)->hours();

        $this->post(route('activation.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'novasenha123',
            'password_confirmation' => 'novasenha123',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Auth::check());
    }
}
