<?php

namespace Tests\Feature;

use App\Enums\ChangeType;
use App\Enums\EndReason;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\ActivateAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_by_name_email_or_nif(): void
    {
        $staff = User::factory()->staff()->create();
        $ana = Client::factory()->for(User::factory()->state(['name' => 'Ana Zebra', 'email' => 'ana@example.com']))->create(['nif' => '111111111']);
        Client::factory()->for(User::factory()->state(['name' => 'Rui Outro', 'email' => 'rui@example.com']))->create(['nif' => '222222222']);

        foreach (['Zebra', 'ana@example', '111111111'] as $term) {
            $this->actingAs($staff)->get(route('crm.clients.index', ['q' => $term]))
                ->assertOk()->assertSee('Ana Zebra')->assertDontSee('Rui Outro');
        }

        $plan = Plan::factory()->create();
        Subscription::factory()->for($ana)->for($plan)->activeFrom(today()->toDateString())->create(['change_type' => ChangeType::Upgrade]);

        $this->actingAs($staff)->get(route('crm.clients.show', $ana))
            ->assertOk()->assertSee('Ana Zebra')->assertSee('Ativa')->assertSee(ChangeType::Upgrade->label());
    }

    public function test_client_role_cannot_access_client_list(): void
    {
        $this->actingAs(User::factory()->create())->get(route('crm.clients.index'))->assertForbidden();
    }

    public function test_cannot_create_subscription_for_deactivated_client(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create(['active' => false]);
        $plan = Plan::factory()->create();
        $period = BillingPeriod::factory()->create();

        $this->actingAs($staff)->get(route('crm.clients.subscriptions.create', $client))
            ->assertRedirect(route('crm.clients.show', $client))->assertSessionHasErrors(['subscription' => 'O aluno está desativado.']);

        $this->actingAs($staff)->post(route('crm.clients.subscriptions.store', $client), [
            'plan_id' => $plan->id, 'billing_period_id' => $period->id, 'payment_method' => 'cash',
        ])->assertSessionHasErrors('subscription');

        $this->assertSame(0, $client->subscriptions()->count());
        $this->actingAs($staff)->get(route('crm.clients.show', $client))->assertDontSee('Nova assinatura');
    }

    public function test_update_client_data(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        $payload = ['name' => 'Novo Nome', 'email' => $client->user->email, 'phone' => '912345678', 'nif' => $client->nif, 'birth_date' => '1990-05-01'];

        $this->actingAs($staff)->put(route('crm.clients.update', $client), $payload)
            ->assertRedirect(route('crm.clients.show', $client));

        $this->assertSame('Novo Nome', $client->user->fresh()->name);
        $this->assertSame('912345678', $client->fresh()->phone);

        $this->actingAs($staff)->put(route('crm.clients.update', $client), [...$payload, 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_cannot_deactivate_with_active_or_overdue_subscription(): void
    {
        $staff = User::factory()->staff()->create();
        $active = Subscription::factory()->activeFrom(today()->toDateString())->create();
        $overdue = Subscription::factory()->activeFrom(today()->subMonth()->subDay()->toDateString())->create();

        foreach ([$active, $overdue] as $subscription) {
            $this->actingAs($staff)->patch(route('crm.clients.deactivate', $subscription->client))
                ->assertSessionHasErrors('subscription');
            $this->assertTrue($subscription->client->fresh()->active);
            $this->assertTrue($subscription->client->user->fresh()->active);
        }
    }

    public function test_deactivate_cancels_pending_subscription(): void
    {
        $staff = User::factory()->staff()->create();
        $subscription = Subscription::factory()->pending()->create();
        $client = $subscription->client;

        $this->actingAs($staff)->patch(route('crm.clients.deactivate', $client))->assertRedirect();

        $this->assertFalse($client->fresh()->active);
        $this->assertFalse($client->user->fresh()->active);
        $this->assertSame(EndReason::Cancelled, $subscription->fresh()->end_reason);
    }

    public function test_resend_activation_sends_notification(): void
    {
        Notification::fake();
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();

        $this->actingAs($staff)->post(route('crm.clients.resend-activation', $client))->assertRedirect();

        Notification::assertSentTo($client->user, ActivateAccount::class);
    }

    public function test_new_subscription_form_reuses_client(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();
        $plan = Plan::factory()->withWindow('06:00', '14:00')->create();
        $period = BillingPeriod::factory()->create();

        $this->actingAs($staff)->get(route('crm.clients.subscriptions.create', $client))
            ->assertOk()->assertDontSee('name="nif"', false);

        $this->actingAs($staff)->post(route('crm.clients.subscriptions.store', $client), [
            'plan_id' => $plan->id,
            'billing_period_id' => $period->id,
            'payment_method' => 'cash',
            'paid_now' => '1',
        ])->assertRedirect(route('crm.clients.show', $client));

        $this->assertSame(1, $client->subscriptions()->count());
        $this->assertSame(1, Client::count());
    }

    public function test_new_subscription_blocked_message_is_shown(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();
        Subscription::factory()->for($client)->create(['ended_on' => today(), 'end_reason' => EndReason::Overdue]);
        $plan = Plan::factory()->create();
        $period = BillingPeriod::factory()->create();

        $url = route('crm.clients.subscriptions.create', $client);
        $this->actingAs($staff)->from($url)->post(route('crm.clients.subscriptions.store', $client), [
            'plan_id' => $plan->id,
            'billing_period_id' => $period->id,
            'payment_method' => 'cash',
        ])->assertRedirect($url)->assertSessionHasErrors(['subscription' => 'O aluno tem uma dívida por regularizar.']);

        $this->actingAs($staff)->followingRedirects()->from($url)->post(route('crm.clients.subscriptions.store', $client), [
            'plan_id' => $plan->id,
            'billing_period_id' => $period->id,
            'payment_method' => 'cash',
        ])->assertSee('O aluno tem uma dívida por regularizar.');
    }

    public function test_deactivate_allowed_when_subscription_is_past_grace_and_ends_with_debt(): void
    {
        $staff = User::factory()->staff()->create();
        $subscription = Subscription::factory()->activeFrom(today()->subMonths(2)->toDateString())->create();
        $client = $subscription->client;

        $this->actingAs($staff)->patch(route('crm.clients.deactivate', $client))->assertSessionDoesntHaveErrors();

        $this->assertFalse($client->fresh()->active);
        $this->assertSame(EndReason::Overdue, $subscription->fresh()->end_reason);
    }

    public function test_activate_reactivates_client_and_allows_new_subscription(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();
        $client->deactivate();
        $plan = Plan::factory()->withWindow('06:00', '14:00')->create();
        $period = BillingPeriod::factory()->create();

        $this->actingAs($staff)->patch(route('crm.clients.activate', $client))->assertSessionHas('status', 'Aluno reativado.');

        $this->assertTrue($client->fresh()->active);
        $this->assertTrue($client->user->fresh()->active);
        $this->actingAs($staff)->post(route('crm.clients.subscriptions.store', $client), [
            'plan_id' => $plan->id, 'billing_period_id' => $period->id, 'payment_method' => 'cash',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(1, $client->subscriptions()->count());
    }

    public function test_resend_activation_refused_for_inactive_client(): void
    {
        Notification::fake();
        $client = Client::factory()->create();
        $client->deactivate();

        $this->actingAs(User::factory()->staff()->create())->post(route('crm.clients.resend-activation', $client))
            ->assertSessionHasErrors('subscription');

        Notification::assertNothingSent();
    }

    public function test_client_page_shows_subscription_actions_and_debt(): void
    {
        $staff = User::factory()->staff()->create();
        $client = Client::factory()->create();
        Subscription::factory()->for($client)->activeFrom('2020-01-10')->create(['ended_on' => '2020-03-01', 'end_reason' => EndReason::Overdue]);
        Subscription::factory()->for($client)->activeFrom(today()->toDateString())->create();

        $this->actingAs($staff)->get(route('crm.clients.show', $client))
            ->assertSee('Marcar como pago')->assertSee('Marcar dívida como paga')->assertSee('Dívida por regularizar');
    }
}
