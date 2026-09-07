<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\Admin;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use App\Models\ScheduledTaskRun;
use App\Services\Core\ScheduledTasksHealthService;
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

    public function test_cron_failure_is_visible_and_escaped_for_admin_with_logs_permission()
    {
        app('translator')->addLines([
            'admin.dashboard.cron_task_failed' => 'La tâche planifiée « :task » a échoué le :date. Erreur : :message',
        ], 'fr');
        Setting::updateSettings([ScheduledTasksHealthService::HEARTBEAT_SETTING => now()], null, false);
        ScheduledTaskRun::create([
            'task_name' => 'services:renewals',
            'status' => ScheduledTaskRun::STATUS_FAILED,
            'executed_at' => now(),
            'error_message' => '<script>alert(1)</script>',
        ]);

        $response = $this->performAdminAction('GET', '/admin/dashboard', [], [Permission::SHOW_LOGS]);

        $response->assertOk();
        $response->assertSee('services:renewals');
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_cron_details_are_hidden_without_logs_permission()
    {
        Setting::updateSettings([ScheduledTasksHealthService::HEARTBEAT_SETTING => now()], null, false);
        ScheduledTaskRun::create([
            'task_name' => 'secret:scheduled-task',
            'status' => ScheduledTaskRun::STATUS_FAILED,
            'executed_at' => now(),
            'error_message' => 'dangerous cron detail',
        ]);

        $response = $this->performAdminAction('GET', '/admin/dashboard', [], [Permission::MANAGE_SETTINGS]);

        $response->assertOk();
        $response->assertDontSee('secret:scheduled-task');
        $response->assertDontSee('dangerous cron detail');
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
