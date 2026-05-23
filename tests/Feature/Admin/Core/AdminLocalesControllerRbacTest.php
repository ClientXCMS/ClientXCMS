<?php

namespace Tests\Feature\Admin\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocalesControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.locales.index'),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_download_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.locales.download', ['locale' => 'fr_FR']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_toggle_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.locales.toggle', ['locale' => 'fr_FR']),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_index_allows_admin_with_manage_settings(): void
    {
        $response = $this->performAdminAction(
            'GET',
            route('admin.locales.index'),
            [],
            ['admin.manage_settings']
        );
        $this->assertNotEquals(403, $response->status());
    }
}
