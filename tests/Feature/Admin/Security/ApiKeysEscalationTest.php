<?php

namespace Tests\Feature\Admin\Security;

use App\Http\Middleware\RequireAdminPassword;
use App\Models\Admin\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeysEscalationTest extends TestCase
{
    use RefreshDatabase;

    private function bootstrapStaff(array $perms, bool $isAdmin = false): Admin
    {
        $this->seed(AdminSeeder::class);
        $this->seed(PermissionSeeder::class);
        $admin = Admin::first();
        $role = $admin->role;
        $role->is_admin = $isAdmin;
        $role->level = 10;
        $role->save();
        $role->permissions()->sync(Permission::whereIn('name', $perms)->pluck('id'));

        return $admin;
    }

    public function test_non_admin_staff_cannot_forge_wildcard_token_via_is_admin_flag(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'pentest-token',
                'is_admin' => 'on',
            ])
            ->assertStatus(403);

        $this->assertNull(
            $admin->fresh()->tokens()->where('name', 'pentest-token')->first(),
            'No token should have been created when is_admin is rejected'
        );
    }

    public function test_super_admin_staff_can_create_wildcard_token(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], true);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'admin-token',
                'is_admin' => 'on',
            ])
            ->assertSessionHas('success');

        $token = $admin->fresh()->tokens()->where('name', 'admin-token')->first();
        $this->assertNotNull($token);
        $this->assertSame(['*'], $token->abilities);
    }

    public function test_non_admin_staff_can_still_create_scoped_token(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'scoped-token',
                'permissions' => ['customers:index' => '1'],
            ])
            ->assertSessionHas('success');

        $token = $admin->fresh()->tokens()->where('name', 'scoped-token')->first();
        $this->assertNotNull($token);
        $this->assertNotContains('*', $token->abilities, 'Scoped token must not contain wildcard ability');
        $this->assertContains('customers:index', $token->abilities);
    }

    public function test_non_admin_staff_cannot_forge_wildcard_token_via_permission_key(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'forged-token',
                'permissions' => ['*' => '1'],
            ])
            ->assertStatus(403);

        $this->assertNull(
            $admin->fresh()->tokens()->where('name', 'forged-token')->first(),
            'No token should have been created when the wildcard is submitted as an ability'
        );
    }

    public function test_abilities_outside_the_offered_list_are_rejected(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'unknown-ability-token',
                'permissions' => ['customers:index' => '1', 'invented:ability' => '1'],
            ])
            ->assertStatus(403);

        $this->assertNull($admin->fresh()->tokens()->where('name', 'unknown-ability-token')->first());
    }

    public function test_scoped_token_carries_the_health_ability_the_routes_expect(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'health-token',
                'permissions' => ['customers:index' => '1'],
            ])
            ->assertSessionHas('success');

        $abilities = $admin->fresh()->tokens()->where('name', 'health-token')->first()->abilities;
        $this->assertContains('health', $abilities);
        $this->assertNotContains('hearth', $abilities);
    }

    public function test_the_requested_expiry_is_applied_to_the_token(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);
        $expiry = now()->addDays(7)->startOfMinute();

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->post(route('admin.api-keys.store'), [
                'name' => 'expiring-token',
                'permissions' => ['customers:index' => '1'],
                'expires_at' => $expiry->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHas('success');

        $token = $admin->fresh()->tokens()->where('name', 'expiring-token')->first();
        $this->assertNotNull($token->expires_at, 'The expiry entered in the form must reach the token');
        $this->assertSame($expiry->toDateTimeString(), $token->expires_at->toDateTimeString());
    }

    public function test_non_admin_staff_cannot_rotate_wildcard_token(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);
        $existing = $admin->createToken('legacy-wildcard', ['*']);

        $this->be($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time()])
            ->put(route('admin.api-keys.rotate', $existing->accessToken->id))
            ->assertStatus(403);

        $this->assertNotNull(
            $admin->fresh()->tokens()->where('id', $existing->accessToken->id)->first(),
            'Original token must not be deleted when rotation is rejected'
        );
    }
}
