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

use App\Models\Personalization\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A copy is a second placement of the same theme file, told apart by its
 * configuration. It used to be a duplicated file, run through a filter that
 * stripped the very directives the section was made of.
 */
class SectionCloneTest extends TestCase
{
    use RefreshDatabase;

    private function section(): Section
    {
        return Section::create([
            'uuid' => 'hero',
            'theme_uuid' => app('theme')->getTheme()->uuid,
            'path' => 'sections/hero',
            'is_active' => true,
            'url' => '/',
            'config' => ['title' => 'Bienvenue'],
        ]);
    }

    public function test_a_copy_points_at_the_same_theme_file(): void
    {
        $clone = $this->section()->cloneSection();

        $this->assertSame('sections/hero', $clone->path);
    }

    public function test_a_copy_writes_no_file_in_the_theme(): void
    {
        $copies = app('theme')->getTheme()->path.'/views/sections_copy';
        $before = File::isDirectory($copies) ? count(File::files($copies)) : 0;

        $this->section()->cloneSection();

        $after = File::isDirectory($copies) ? count(File::files($copies)) : 0;
        $this->assertSame($before, $after, 'Cloning wrote into the theme directory.');
    }

    public function test_a_copy_is_persisted_and_carries_its_own_configuration(): void
    {
        $section = $this->section();

        $clone = $section->cloneSection();

        $this->assertTrue($clone->exists);
        $this->assertNotSame($section->id, $clone->id);
        $this->assertSame(['title' => 'Bienvenue'], $clone->config);
    }

    /**
     * Two sections now share a path, so anything keyed by path would serve one
     * section's markup for the other.
     */
    public function test_two_copies_are_told_apart_by_their_identifier(): void
    {
        $section = $this->section();
        $clone = $section->cloneSection();

        $this->assertSame($section->path, $clone->path);
        $this->assertSame($section->id, $section->toDTO()->json['id']);
        $this->assertSame($clone->id, $clone->toDTO()->json['id']);
    }

    public function test_deleting_a_copy_leaves_the_theme_file_alone(): void
    {
        $section = $this->section();
        $clone = $section->cloneSection();

        $clone->delete();

        // Sections are soft deleted: the row stays, the section is gone.
        $this->assertSoftDeleted('theme_sections', ['id' => $clone->id]);
        $this->assertNotSoftDeleted('theme_sections', ['id' => $section->id]);
    }
}
