<?php

namespace Tests\Feature\Admin\Helpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskSettingsControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settings_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'PUT',
            '/admin/settings/helpdesk/settings',
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_show_settings_allows_admin_with_manage_settings(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.settings.helpdesk'),
            [],
            ['admin.manage_settings']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_SETTINGS must not be blocked');
    }
}
