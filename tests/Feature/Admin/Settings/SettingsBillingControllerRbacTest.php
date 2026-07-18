<?php

namespace Tests\Feature\Admin\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsBillingControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_billing_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'PUT',
            route('admin.settings.store.billing.save'),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_save_billing_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'PUT',
            route('admin.settings.store.billing.save'),
            [],
            ['admin.manage_settings']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_SETTINGS must not be blocked');
    }
}
