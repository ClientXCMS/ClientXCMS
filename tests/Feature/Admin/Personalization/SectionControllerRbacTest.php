<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Personalization\Section;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function createSection(): Section
    {
        $this->seed(ThemeSeeder::class);

        return Section::create([
            'uuid' => 'pentest-rbac-'.uniqid(),
            'theme_uuid' => 'default',
            'path' => 'sections.pentest_rbac',
            'is_active' => true,
            'url' => '/',
        ]);
    }

    public function test_section_show_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.sections.show', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_update_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.personalization.sections.update', $section),
            [
                'content' => 'safe content',
                'url' => '/',
                'theme_uuid' => 'default',
            ],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_destroy_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.personalization.sections.destroy', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_switch_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.switch', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_clone_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.clone', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_restore_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.restore', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_sort_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.sort'),
            ['items' => [$section->id]],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_clone_section_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.clone_section', ['section' => $section->uuid]),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_update_config_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.personalization.sections.config.update', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_section_switch_allows_admin_with_personalization_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'POST',
            route('admin.personalization.sections.switch', $section),
            [],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_PERSONALIZATION must not be blocked');
    }
}
