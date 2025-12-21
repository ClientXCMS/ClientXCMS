<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_dashboard()
    {
        $response = $this->performAdminAction('GET', '/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_earn_requires_password_confirmation()
    {
        $this->seed(AdminSeeder::class);

        $this->actingAs(Admin::first(), 'admin');

        session()->forget('auth.password_confirmed_at');

        $response = $this->get('/admin/earn');

        $response->assertRedirect('/admin/confirm-password');
    }

    public function test_admin_earn_after_password_confirmed()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->put('auth.password_confirmed_at', time());

        $response = $this->get('/admin/earn');
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.earn');
    }

    public function test_admin_license_requires_password_confirmation()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->forget('auth.password_confirmed_at');

        $response = $this->get('/admin/license');
        $response->assertRedirect('/admin/confirm-password');
    }

    public function test_admin_license_after_password_confirmed()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->put('auth.password_confirmed_at', time());
        $response = $this->get('/admin/license');
        $response->assertStatus(200);
    }

    public function test_admin_earn_with_invalid_permission()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->put('auth.password_confirmed_at', time());
        $response = $this->performAdminAction('GET', '/admin/earn', [], ['admin.dashboard']);
        $response->assertStatus(403);
    }

    public function test_admin_license()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->put('auth.password_confirmed_at', time());
        $response = $this->performAdminAction('GET', '/admin/license');
        $response->assertStatus(200);
    }

    public function test_admin_license_with_invalid_permission()
    {
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');
        session()->put('auth.password_confirmed_at', time());
        $response = $this->performAdminAction('GET', '/admin/license', [], ['admin.dashboard']);
        $response->assertStatus(403);
    }
}
