<?php

namespace Tests\Feature\Admin\Helpdesk;

use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartmentControllerTest extends \Tests\TestCase
{
    const API_URL = 'admin/helpdesk/departments';

    use RefreshDatabase;

    public function test_admin_department_index(): void
    {
        $id = SupportDepartment::create([
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ])->id;
        $response = $this->performAdminAction('get', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_department_index_without_permission(): void
    {
        $response = $this->performAdminAction('get', self::API_URL, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_department_get(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $id = SupportDepartment::create([
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ])->id;
        $response = $this->performAdminAction('get', self::API_URL.'/'.$id);
        $response->assertStatus(200);
    }

    public function test_admin_department_update(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $id = SupportDepartment::create([
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ])->id;
        $response = $this->performAdminAction('put', self::API_URL.'/'.$id, [
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_department_store(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $response = $this->performAdminAction('post', self::API_URL, [
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_department_delete(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $id = SupportDepartment::create([
            'name' => 'Test Department',
            'description' => 'Test Department Description',
            'icon' => 'bi bi-hdd',
        ])->id;
        $response = $this->performAdminAction('delete', self::API_URL.'/'.$id);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
