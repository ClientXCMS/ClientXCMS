<?php

namespace Tests\Feature\Admin\Billing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpgradeControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.upgrades.index'),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_index_allows_admin_with_manage_services(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.upgrades.index'),
            [],
            ['admin.manage_services']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_SERVICES must not be blocked');
    }
}
