<?php

namespace Tests\Feature\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 3.6 - Bulk Selection Mode
 *
 * Verifies the extensions index page renders the bulk action bar partial
 * with required ARIA attributes, action buttons, and toggle button.
 * Also verifies enable/disable endpoints accept sequential AJAX calls
 * as used by the bulk JS pipeline.
 */
class ExtensionBulkModeTest extends TestCase
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

    private function getExtensionsPage()
    {
        $admin = $this->seedAndGetAdmin();

        // Provide a group with one installed extension so bulk partials render.
        $dto = new ExtensionDTO('test-mod', 'module', true, [
            'name' => 'Test',
            'translations' => ['name' => ['en' => 'Test'], 'short_description' => ['en' => 'Test']],
            'thumbnail' => '', 'formatted_price' => 'Free', 'price' => 0,
            'group_uuid' => 'test', 'tags' => [],
        ], '1.0.0');
        $dto->installed = true;

        $mock = \Mockery::mock(ExtensionManager::class);
        $mock->shouldReceive('getGroupsWithExtensions')
            ->andReturn(['Test' => ['items' => collect([$dto]), 'icon' => 'bi bi-puzzle']]);
        $mock->shouldReceive('fetch')
            ->andReturn(['tags' => []]);
        $mock->shouldReceive('getAdminMenuItems')
            ->andReturn(collect([]));
        $mock->shouldIgnoreMissing();
        $this->app->instance('extension', $mock);

        return $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));
    }

    // -- Bulk Action Bar Rendering --

    public function test_index_page_renders_bulk_action_bar(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="bulk-action-bar"', false);
    }

    public function test_bulk_action_bar_is_hidden_by_default(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        // The bar must be hidden until bulk mode is activated via JS
        $response->assertSee('id="bulk-action-bar"', false);
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/id="bulk-action-bar"\s+class="hidden fixed/',
            $content,
            'Bulk action bar must be hidden by default'
        );
    }

    // -- ARIA Attributes (AC7) --

    public function test_bulk_action_bar_has_toolbar_role(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('role="toolbar"', false);
    }

    public function test_bulk_action_bar_has_aria_label(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('aria-label="Actions group', false);
    }

    // -- Bulk Action Buttons --

    public function test_bulk_activate_button_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-action="bulk-activate"', false);
    }

    public function test_bulk_deactivate_button_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-action="bulk-deactivate"', false);
    }

    public function test_bulk_cancel_button_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-action="bulk-cancel"', false);
    }

    public function test_bulk_activate_button_is_disabled_by_default(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        // Buttons disabled until checkboxes are selected
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/data-action="bulk-activate"[^>]*disabled/',
            $content,
            'Bulk activate button should be disabled by default'
        );
    }

    public function test_bulk_deactivate_button_is_disabled_by_default(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/data-action="bulk-deactivate"[^>]*disabled/',
            $content,
            'Bulk deactivate button should be disabled by default'
        );
    }

    // -- Toggle Button --

    public function test_toggle_bulk_mode_button_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-action="toggle-bulk-mode"', false);
    }

    // -- Selection Count Display --

    public function test_bulk_count_element_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="bulk-count"', false);
    }

    // -- CSS Bulk Mode Styles --

    public function test_bulk_mode_css_styles_present(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        // Verify both :has() and .bulk-selected fallback selectors exist
        $response->assertSee('.bulk-mode-active .extension-item:has(.bulk-checkbox:checked)', false);
        $response->assertSee('.bulk-mode-active .extension-item.bulk-selected', false);
    }

    // -- Sequential Enable/Disable Endpoints (used by bulk JS) --

    public function test_sequential_enable_calls_return_json(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'bulk-mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
                ['uuid' => 'bulk-mod-2', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ]);

        // Bulk mode sends sequential AJAX calls to enable each extension
        $response1 = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.enable', ['type' => 'modules', 'extension' => 'bulk-mod-1']));

        $response2 = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.enable', ['type' => 'modules', 'extension' => 'bulk-mod-2']));

        // Both should return a JSON response (success or controlled error)
        $response1->assertJsonStructure(['success', 'message', 'data', 'errors']);
        $response2->assertJsonStructure(['success', 'message', 'data', 'errors']);
    }

    public function test_sequential_disable_calls_return_json(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'bulk-mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
                ['uuid' => 'bulk-mod-2', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ]);

        $response1 = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'bulk-mod-1']));

        $response2 = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'bulk-mod-2']));

        $response1->assertJsonStructure(['success', 'message', 'data', 'errors']);
        $response2->assertJsonStructure(['success', 'message', 'data', 'errors']);
    }

    // -- Authentication Required --

    public function test_bulk_mode_page_requires_authentication(): void
    {
        $response = $this->get(route('admin.settings.extensions.index'));

        $response->assertRedirect();
    }
}
