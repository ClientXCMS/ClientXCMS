<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace Tests\Feature\Admin\Personalization;

use App\Models\Admin\Admin;
use App\Models\Personalization\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Section markup belongs to the theme. The admin decides where a section shows
 * and what its declared fields hold, never what it is made of.
 */
class SectionMarkupIsNotEditableTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    private function section(): Section
    {
        return Section::create([
            'uuid' => 'hero',
            'theme_uuid' => app('theme')->getTheme()->uuid,
            'path' => 'sections/hero',
            'is_active' => true,
            'url' => '/',
        ]);
    }

    public function test_posting_markup_does_not_store_it(): void
    {
        $section = $this->section();
        $hostile = '<script>alert(1)</script>';

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.personalization.sections.update', $section),
            ['url' => '/', 'theme_uuid' => $section->theme_uuid, 'content' => $hostile],
        );

        $this->assertDatabaseMissing('theme_sections', ['id' => $section->id, 'path' => 'sections_copy/'.$section->id.'-hero']);
        $this->assertSame('sections/hero', $section->fresh()->path, 'The section still points at the theme file.');
    }

    public function test_posting_markup_writes_no_file_in_the_theme(): void
    {
        $section = $this->section();
        $copies = app('theme')->getTheme()->path.'/views/sections_copy';
        $before = File::isDirectory($copies) ? File::files($copies) : [];

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.personalization.sections.update', $section),
            ['url' => '/', 'theme_uuid' => $section->theme_uuid, 'content' => '<script>alert(1)</script>'],
        );

        $after = File::isDirectory($copies) ? File::files($copies) : [];
        $this->assertCount(count($before), $after, 'A section update wrote into the theme directory.');
    }

    /**
     * What the admin is still allowed to do has to keep working, otherwise this
     * change removed more than the markup.
     */
    public function test_the_admin_can_still_move_a_section(): void
    {
        $section = $this->section();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.personalization.sections.update', $section),
            ['url' => '/contact', 'theme_uuid' => $section->theme_uuid, 'is_active' => 'on'],
        );

        $fresh = $section->fresh();
        $this->assertSame('/contact', $fresh->url);
        $this->assertTrue((bool) $fresh->is_active);
    }

    public function test_the_model_no_longer_offers_a_way_to_write_markup(): void
    {
        $this->assertFalse(
            method_exists(Section::class, 'saveContent'),
            'saveContent is back: markup can be written from the admin again.',
        );
    }
}
