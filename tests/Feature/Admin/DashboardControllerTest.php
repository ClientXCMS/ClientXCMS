<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Tests\Feature\Admin;

use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
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
