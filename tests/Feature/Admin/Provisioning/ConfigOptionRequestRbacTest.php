<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Billing\ConfigOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigOptionRequestRbacTest extends TestCase
{
    use RefreshDatabase;

    private function configOption(): ConfigOption
    {
        return ConfigOption::create([
            'type' => 'text',
            'key' => 'pentest_key',
            'name' => 'Pentest Option',
            'sort_order' => 1,
            'hidden' => 0,
            'required' => 0,
        ]);
    }

    public function test_store_form_request_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.configoptions.store'),
            ['type' => 'text', 'key' => 'x', 'name' => 'x'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_update_options_form_request_blocks_admin_without_permission(): void
    {
        $option = $this->configOption();
        $response = $this->performAdminAction(
            'POST',
            route('admin.configoptions.update_options', $option),
            ['options' => [['friendly_name' => 'x', 'value' => 'x']]],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_store_form_request_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.configoptions.store'),
            [],
            ['admin.manage_configoptions']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_CONFIGOPTIONS must not be blocked by authorize()');
    }
}
