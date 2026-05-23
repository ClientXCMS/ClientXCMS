<?php

namespace Tests\Feature\Admin\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsExtensionControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_extensions_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.settings.extensions.index'),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_show_extensions_allows_admin_with_manage_extensions(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.settings.extensions.index'),
            [],
            ['admin.manage_extensions']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_EXTENSIONS must not be blocked');
    }
}
