<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\RequireAdminPassword;
use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationGuardScopeTest extends TestCase
{
    use RefreshDatabase;

    private function staffMember(): Admin
    {
        $this->seed(AdminSeeder::class);
        $this->seed(PermissionSeeder::class);
        $admin = Admin::first();
        $role = $admin->role;
        $role->is_admin = true;
        $role->level = 10;
        $role->save();
        $role->permissions()->sync(Permission::whereIn('name', ['admin.manage_api_keys'])->pluck('id'));

        return $admin;
    }

    public function test_customer_password_confirmation_does_not_unlock_admin_routes(): void
    {
        $customer = Customer::factory()->create();
        $admin = $this->staffMember();

        $this->actingAs($customer, 'web');
        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect(route('front.profile.index'));

        $this->actingAs($admin, 'admin');
        $this->get(route('admin.api-keys.index'))
            ->assertRedirect(route('admin.password.confirm'));
    }

    public function test_admin_password_confirmation_unlocks_admin_routes(): void
    {
        $admin = $this->staffMember();

        $this->actingAs($admin, 'admin');
        $this->post(route('admin.password.confirm'), ['password' => 'password'])
            ->assertRedirect();

        $this->get(route('admin.api-keys.index'))->assertOk();
    }

    public function test_admin_password_confirmation_does_not_unlock_customer_passkey_management(): void
    {
        Setting::updateSettings('passkeys_enabled', 'true');
        $customer = Customer::factory()->create();
        $admin = $this->staffMember();

        $this->actingAs($admin, 'admin');
        $this->post(route('admin.password.confirm'), ['password' => 'password'])
            ->assertRedirect();

        $this->actingAs($customer, 'web');
        $this->get(route('front.profile.passkeys.options'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_admin_confirmation_expires_with_the_configured_timeout(): void
    {
        config(['auth.password_timeout' => 60]);
        $admin = $this->staffMember();

        $this->actingAs($admin, 'admin')
            ->withSession([RequireAdminPassword::SESSION_KEY => time() - 61])
            ->get(route('admin.api-keys.index'))
            ->assertRedirect(route('admin.password.confirm'));
    }
}
