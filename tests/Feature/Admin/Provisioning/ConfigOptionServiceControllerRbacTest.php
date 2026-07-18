<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Account\Customer;
use App\Models\Provisioning\ConfigOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigOptionServiceControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function configOptionService(): ConfigOptionService
    {
        $customer = Customer::factory()->create();
        $service = $this->createServiceModel($customer->id);
        $option = $this->createOptionModel();

        $cos = new ConfigOptionService;
        $cos->config_option_id = $option->id;
        $cos->service_id = $service->id;
        $cos->value = 'test';
        $cos->key = $option->key;
        $cos->save();

        return $cos;
    }

    public function test_show_blocks_admin_without_permission(): void
    {
        $cos = $this->configOptionService();
        $response = $this->performAdminAction(
            'GET',
            route('admin.configoptions_services.show', $cos),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_create_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.configoptions_services.create'),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_store_blocks_admin_without_permission(): void
    {
        $cos = $this->configOptionService();
        $response = $this->performAdminAction(
            'POST',
            route('admin.configoptions_services.store'),
            [
                'value' => 'tampered',
                'config_option_id' => $cos->config_option_id,
                'service_id' => $cos->service_id,
            ],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_update_blocks_admin_without_permission(): void
    {
        $cos = $this->configOptionService();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.configoptions_services.update', $cos),
            ['value' => 'tampered'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $cos->refresh();
        $this->assertSame('test', $cos->value);
    }

    public function test_destroy_blocks_admin_without_permission(): void
    {
        $cos = $this->configOptionService();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.configoptions_services.destroy', $cos),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $this->assertDatabaseHas('config_options_services', ['id' => $cos->id]);
    }

    public function test_show_allows_admin_with_manage_configoptions(): void
    {
        $cos = $this->configOptionService();
        $response = $this->performAdminAction(
            'GET',
            route('admin.configoptions_services.show', $cos),
            [],
            ['admin.manage_configoptions']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_CONFIGOPTIONS must not be blocked');
    }
}
