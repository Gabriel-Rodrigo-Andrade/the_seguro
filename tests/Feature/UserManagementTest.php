<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['role' => UserRole::Administrator]);
    }

    private function attributes(User $user, array $changes = []): array
    {
        return array_replace(['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'is_active' => true], $changes);
    }

    public function test_guests_are_redirected_and_customers_and_brokers_cannot_manage_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $target = User::factory()->create();
        foreach ([UserRole::Customer, UserRole::Broker] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->get('/admin/users')->assertForbidden();
            $this->get('/admin/users/create')->assertForbidden();
            $this->get('/admin/users/'.$target->id)->assertForbidden();
            $this->get('/admin/users/'.$target->id.'/edit')->assertForbidden();
            $this->post('/admin/users', [])->assertForbidden();
            $this->put('/admin/users/'.$target->id, $this->attributes($target))->assertForbidden();
            $this->patch('/admin/users/'.$target->id.'/deactivate')->assertForbidden();
        }
    }

    public function test_admin_can_create_view_edit_and_deactivate_without_deleting(): void
    {
        $this->actingAs($this->administrator());
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->post('/admin/users', ['name' => 'Novo Corretor', 'email' => 'NOVO@EXAMPLE.TEST', 'role' => 'corretor', 'is_active' => 1, 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertSessionHasNoErrors();
        $user = User::where('email', 'novo@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->get('/admin/users/'.$user->id)->assertOk()->assertSee('Corretor');
        $this->get('/admin/users/'.$user->id.'/edit')->assertOk();
        $this->put('/admin/users/'.$user->id, $this->attributes($user, ['name' => 'Nome atualizado']))->assertSessionHasNoErrors();
        $this->assertSame('Nome atualizado', $user->fresh()->name);
        $this->patch('/admin/users/'.$user->id.'/deactivate')->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
        $this->delete('/admin/users/'.$user->id)->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }

    public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/deactivate')->assertSessionHasErrors('is_active');
        $this->put('/admin/users/'.$admin->id, $this->attributes($admin, ['role' => 'cliente']))->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame(UserRole::Administrator, $admin->fresh()->role);
    }

    public function test_inactive_admin_does_not_allow_removal_of_last_active_admin(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['role' => 'administrador', 'is_active' => false]);
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/deactivate')->assertSessionHasErrors('is_active');
    }

    public function test_admin_can_be_demoted_when_another_active_admin_remains(): void
    {
        $actor = $this->administrator();
        $target = $this->administrator();
        $this->actingAs($actor)->put('/admin/users/'.$target->id, $this->attributes($target, ['role' => 'corretor']))->assertSessionHasNoErrors();
        $this->assertSame(UserRole::Broker, $target->fresh()->role);
    }

    public function test_self_demotion_redirects_to_dashboard_and_revokes_admin_access(): void
    {
        $actor = $this->administrator();
        $this->administrator();
        $this->actingAs($actor)->put('/admin/users/'.$actor->id, $this->attributes($actor, ['role' => 'cliente']))->assertRedirect('/dashboard');
        $this->actingAs($actor->fresh())->get('/admin/users')->assertForbidden();
    }

    public function test_public_registration_ignores_privileged_fields(): void
    {
        $this->post('/register', ['name' => 'Cliente', 'email' => 'CLIENTE@EXAMPLE.TEST', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'administrador', 'is_active' => false])->assertRedirect('/dashboard');
        $user = User::where('email', 'cliente@example.test')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_old_session_is_invalidated_after_deactivation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_all_roles_can_login_and_logout(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'password'])->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertOk()->assertSee($role->label());
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_admin_input_validation_rejects_invalid_role_duplicate_email_and_empty_name(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create();
        $this->actingAs($admin)->put('/admin/users/'.$user->id, $this->attributes($user, ['name' => '', 'role' => 'root', 'email' => strtoupper($admin->email)]))->assertSessionHasErrors(['name', 'role', 'email']);
        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public function test_seed_reexecution_preserves_existing_account_changes(): void
    {
        $this->seed(UserSeeder::class);
        $user = User::where('email', 'cliente1@theseguro.test')->firstOrFail();
        $user->name = 'Nome preservado';
        $user->is_active = false;
        $user->save();
        $hash = $user->password;
        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 4);
        $this->assertSame('Nome preservado', $user->fresh()->name);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame($hash, $user->fresh()->password);
    }
}
