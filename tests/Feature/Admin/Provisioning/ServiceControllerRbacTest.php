<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        $customer = Customer::factory()->create();

        return $this->createServiceModel($customer->id);
    }

    public function test_tab_blocks_admin_without_permission(): void
    {
        $service = $this->service();
        $response = $this->performAdminAction(
            'GET',
            route('admin.services.tab', ['service' => $service, 'tab' => 'overview']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_tab_allows_admin_with_show_services_permission(): void
    {
        $service = $this->service();
        $response = $this->performAdminAction(
            'GET',
            route('admin.services.tab', ['service' => $service, 'tab' => 'overview']),
            [],
            ['admin.show_services']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with SHOW_SERVICES must not be blocked');
    }
}
