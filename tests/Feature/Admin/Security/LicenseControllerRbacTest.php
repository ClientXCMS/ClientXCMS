<?php

namespace Tests\Feature\Admin\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_return_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('licensing.return', ['code' => 'pentest-fake-code']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_oauth_return_allows_admin_with_manage_license(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('licensing.return'),
            [],
            ['admin.manage_license']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_LICENSE must not be blocked');
    }
}
