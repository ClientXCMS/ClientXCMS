<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Personalization\Section;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createSection(string $path = 'sections.partners'): Section
    {
        $this->seed(ThemeSeeder::class);

        return Section::create([
            'uuid' => 'partners',
            'theme_uuid' => 'default',
            'path' => $path,
            'is_active' => true,
            'url' => '/',
        ]);
    }

    public function test_preview_blocks_admin_without_permission(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.sections.preview', $section),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_preview_returns_404_when_view_is_missing(): void
    {
        $section = $this->createSection('sections.does_not_exist');
        $response = $this->performAdminAction('GET', route('admin.personalization.sections.preview', $section));
        $response->assertStatus(404);
    }

    public function test_preview_renders_section_inside_theme_layout(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction('GET', route('admin.personalization.sections.preview', $section));
        $response->assertOk();
        $response->assertSee('id="section-preview"', false);
        $response->assertSee('<base target="_blank">', false);
    }

    public function test_show_embeds_preview_iframe_instead_of_raw_markup(): void
    {
        $section = $this->createSection();
        $response = $this->performAdminAction('GET', route('admin.personalization.sections.show', $section));
        $response->assertOk();
        $response->assertSee(route('admin.personalization.sections.preview', $section), false);
        $response->assertDontSee('id="section-preview"', false);
    }
}
