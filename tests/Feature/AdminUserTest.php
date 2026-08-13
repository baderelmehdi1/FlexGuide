<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        PermissionRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_non_admin_cannot_reach_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_admin_sees_users_with_their_roles(): void
    {
        PermissionRole::findOrCreate(Role::Viewer->value, 'web');
        $admin = $this->admin();
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users')
                ->where('users', fn ($users) => collect($users)->firstWhere('id', $viewer->id)['role'] === 'viewer')
            );
    }

    public function test_admin_can_change_a_users_role(): void
    {
        PermissionRole::findOrCreate(Role::Viewer->value, 'web');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $admin = $this->admin();
        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'contributor'])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasRole('contributor'));
        $this->assertFalse($user->fresh()->hasRole('viewer'));
    }

    public function test_invalid_role_is_rejected(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'superadmin'])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Hire',
                'email' => 'new.hire@flexcube.local',
                'password' => 'a-strong-password',
                'role' => 'contributor',
            ])
            ->assertRedirect();

        $user = User::firstWhere('email', 'new.hire@flexcube.local');
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('contributor'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-strong-password', $user->password));
    }

    public function test_user_creation_rejects_a_duplicate_email(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Duplicate',
                'email' => $existing->email,
                'password' => 'a-strong-password',
                'role' => 'viewer',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_user_creation_rejects_a_short_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Weak Password',
                'email' => 'weak@flexcube.local',
                'password' => 'short',
                'role' => 'viewer',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_non_admin_cannot_create_a_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/users', [
                'name' => 'New Hire',
                'email' => 'new.hire@flexcube.local',
                'password' => 'a-strong-password',
                'role' => 'viewer',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/password", ['password' => 'brand-new-password'])
            ->assertRedirect();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_password_reset_rejects_a_short_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/password", ['password' => 'short'])
            ->assertSessionHasErrors('password');
    }
}
