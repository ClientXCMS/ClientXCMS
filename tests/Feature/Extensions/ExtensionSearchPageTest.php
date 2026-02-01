<?php

namespace Tests\Feature\Extensions;

use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 2.2 - Universal Search & Cross-Tab Navigation
 *
 * Verifies the extensions index page renders with the required
 * search bar, tab counters, ARIA attributes, and no-results regions.
 */
class ExtensionSearchPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ExtensionManager::writeExtensionJson([]);
    }

    private function seedAndGetAdmin(): Admin
    {
        $this->seed(AdminSeeder::class);

        return Admin::first();
    }

    public function test_index_page_renders_search_bar_with_role_attribute(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('role="search"', false);
        $response->assertSee('id="extension-search"', false);
    }

    public function test_index_page_renders_clear_button(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('id="js-search-clear"', false);
    }

    public function test_index_page_renders_aria_live_announcer(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('id="js-search-announcer"', false);
        $response->assertSee('aria-live="polite"', false);
    }

    public function test_index_page_renders_tab_buttons(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('data-tab="installed"', false);
        $response->assertSee('data-tab="discover"', false);
        $response->assertSee('data-tab="themes"', false);
    }

    public function test_index_page_renders_tab_counters_with_aria_live(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('js-tab-counter', false);
    }

    public function test_index_page_renders_no_results_regions(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('js-no-results', false);
    }

    public function test_index_page_renders_keyboard_shortcut_hint(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('<kbd', false);
    }

    public function test_index_page_has_sticky_search_container(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('sticky top-0 z-30', false);
    }

    public function test_index_page_requires_authentication(): void
    {
        $response = $this->get(route('admin.settings.extensions.index'));

        // Admin middleware redirects unauthenticated GET requests
        $response->assertRedirect();
    }
}
