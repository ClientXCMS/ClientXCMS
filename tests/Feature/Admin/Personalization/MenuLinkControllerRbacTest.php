<?php

namespace Tests\Feature\Admin\Personalization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuLinkControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.menulinks.create', ['type' => 'front']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_create_form_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.menulinks.create', ['type' => 'front']),
            [],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status());
    }
}
