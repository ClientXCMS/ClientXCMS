<?php

namespace Tests\Feature\Admin\Security;

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
            ->withSession(['auth.password_confirmed_at' => time()])
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
            ->withSession(['auth.password_confirmed_at' => time()])
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
            ->withSession(['auth.password_confirmed_at' => time()])
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

    public function test_non_admin_staff_cannot_rotate_wildcard_token(): void
    {
        Setting::updateSettings(['password_timeout' => '999999']);

        $admin = $this->bootstrapStaff(['admin.manage_api_keys'], false);
        $existing = $admin->createToken('legacy-wildcard', ['*']);

        $this->be($admin, 'admin')
            ->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.api-keys.rotate', $existing->accessToken->id))
            ->assertStatus(403);

        $this->assertNotNull(
            $admin->fresh()->tokens()->where('id', $existing->accessToken->id)->first(),
            'Original token must not be deleted when rotation is rejected'
        );
    }
}
