<?php

namespace Tests\Feature\Api\Application\Helpdesk;

use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    const API_URL = 'api/application/departments';

    const ABILITY_INDEX = 'departments:index';

    const ABILITY_STORE = 'departments:store';

    const ABILITY_SHOW = 'departments:show';

    const ABILITY_UPDATE = 'departments:update';

    const ABILITY_DELETE = 'departments:delete';

    use RefreshDatabase;

    public function test_api_application_department_index(): void
    {
        SupportDepartment::factory(5)->create();
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_department_filter(): void
    {
        SupportDepartment::factory()->create(['name' => 'Technical Support']);
        $response = $this->performAction('GET', self::API_URL.'?filter[name]=Fake', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_api_application_department_sort(): void
    {
        SupportDepartment::factory(5)->create();
        $lastDepartment = SupportDepartment::orderBy('id', 'desc')->first();
        $response = $this->performAction('GET', self::API_URL.'?sort=-id', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertEquals($lastDepartment->id, $response->json('data.0.id'));
    }

    public function test_api_application_department_store(): void
    {
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'name' => 'Technical Support',
            'description' => 'Handles all technical issues',
            'icon' => 'bi bi-question-circle',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('support_departments', [
            'name' => 'Technical Support',
        ]);
    }

    public function test_api_application_department_show(): void
    {
        $department = SupportDepartment::factory()->create();
        $response = $this->performAction('GET', self::API_URL.'/'.$department->id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_department_update(): void
    {
        $department = SupportDepartment::factory()->create();
        $response = $this->performAction('POST', self::API_URL.'/'.$department->id, [self::ABILITY_UPDATE], [
            'name' => 'Updated Department',
            'description' => 'Updated description',
            'icon' => 'bi bi-gear',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Department']);
    }

    public function test_api_application_department_delete(): void
    {
        $department = SupportDepartment::factory()->create();
        $response = $this->performAction('DELETE', self::API_URL.'/'.$department->id, [self::ABILITY_DELETE]);
        $response->assertStatus(204);
        $this->assertSoftDeleted('support_departments', ['id' => $department->id]);
    }

    public function test_api_application_department_delete_with_tickets(): void
    {
        $department = SupportDepartment::factory()->create();
        // Create a customer and ticket for this department
        $customer = \App\Models\Account\Customer::factory()->create();
        \App\Models\Helpdesk\SupportTicket::factory()->create([
            'department_id' => $department->id,
            'customer_id' => $customer->id,
        ]);
        
        $response = $this->performAction('DELETE', self::API_URL.'/'.$department->id, [self::ABILITY_DELETE]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('support_departments', ['id' => $department->id]);
    }
}
