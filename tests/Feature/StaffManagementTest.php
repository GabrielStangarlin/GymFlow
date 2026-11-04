<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_staff_member(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('crm.staff.store'), [
            'name' => 'Ana Silva',
            'email' => 'ana@example.com',
            'password' => 'segredo123',
            'password_confirmation' => 'segredo123',
        ])->assertRedirect(route('crm.staff.index'));

        $created = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame(Role::Staff, $created->role);
        $this->assertTrue($created->active);
        $this->assertTrue(Hash::check('segredo123', $created->password));
    }

    public function test_staff_cannot_manage_staff(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('crm.staff.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('crm.staff.create'))->assertForbidden();
        $this->actingAs($staff)->patch(route('crm.staff.deactivate', $staff))->assertForbidden();
    }

    public function test_admin_deactivates_and_reactivates_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($admin)->get(route('crm.staff.index'))->assertOk()->assertSee($staff->email);

        $this->actingAs($admin)->patch(route('crm.staff.deactivate', $staff))->assertRedirect();
        $this->assertFalse($staff->fresh()->active);

        $this->actingAs($admin)->patch(route('crm.staff.activate', $staff))->assertRedirect();
        $this->assertTrue($staff->fresh()->active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('crm.staff.index'))
            ->patch(route('crm.staff.deactivate', $admin))
            ->assertRedirect(route('crm.staff.index'))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->active);
    }
}
