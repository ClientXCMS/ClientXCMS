<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Personalization\SocialNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCrudControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function social(): SocialNetwork
    {
        return SocialNetwork::create([
            'name' => 'Test',
            'icon' => 'bi bi-test',
            'url' => 'https://example.com',
            'position' => 0,
        ]);
    }

    public function test_store_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.socials.store'),
            ['name' => 'Pentest', 'icon' => 'bi bi-x', 'url' => 'https://evil.tld'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $this->assertDatabaseMissing('theme_socialnetworks', ['name' => 'Pentest']);
    }

    public function test_update_blocks_admin_without_permission(): void
    {
        $social = $this->social();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.personalization.socials.update', $social),
            ['name' => 'PWNED', 'icon' => 'bi bi-x', 'url' => 'https://evil.tld'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $social->refresh();
        $this->assertSame('Test', $social->name);
    }

    public function test_destroy_blocks_admin_without_permission(): void
    {
        $social = $this->social();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.personalization.socials.destroy', $social),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $this->assertDatabaseHas('theme_socialnetworks', ['id' => $social->id]);
    }

    public function test_store_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.socials.store'),
            ['name' => 'LegitName', 'icon' => 'bi bi-x', 'url' => 'https://example.com'],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status());
        $this->assertDatabaseHas('theme_socialnetworks', ['name' => 'LegitName']);
    }
}
