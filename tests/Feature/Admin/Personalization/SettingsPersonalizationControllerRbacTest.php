<?php

namespace Tests\Feature\Admin\Personalization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPersonalizationControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_custom_menu_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.menulinks.custom', ['type' => 'main']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_show_custom_menu_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.menulinks.custom', ['type' => 'main']),
            [],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status());
    }
}
