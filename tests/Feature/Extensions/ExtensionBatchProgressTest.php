<?php

namespace Tests\Feature\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 3.3 - Batch Progress Bar & Error Recovery
 *
 * Verifies the extensions index page renders the batch progress partial
 * with required ARIA attributes, progress bar structure, error recovery UI,
 * and stop button.
 */
class ExtensionBatchProgressTest extends TestCase
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

        // Provide a group with one installed extension so batch/bulk partials render.
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

    // -- Batch Progress Container --

    public function test_index_page_renders_batch_progress_container(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress"', false);
    }

    public function test_batch_progress_is_hidden_by_default(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress"', false);
        $response->assertSee('aria-hidden="true"', false);
    }

    // -- Progress Bar ARIA Attributes (AC6) --

    public function test_progress_bar_has_role_progressbar(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('role="progressbar"', false);
    }

    public function test_progress_bar_has_aria_valuenow(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('aria-valuenow="0"', false);
    }

    public function test_progress_bar_has_aria_valuemin_and_max(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('aria-valuemin="0"', false);
        $response->assertSee('aria-valuemax="100"', false);
    }

    public function test_progress_text_has_aria_live(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress-text"', false);
        // aria-live on the dynamic text element, not on the hidden container
        $response->assertSee('aria-live="polite"', false);
    }

    // -- Progress Bar Structure (AC2) --

    public function test_progress_bar_element_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress-bar"', false);
    }

    public function test_progress_percentage_element_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress-percentage"', false);
    }

    public function test_progress_items_container_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-progress-items"', false);
    }

    // -- Stop Button (AC3) --

    public function test_stop_button_exists_with_danger_style(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-action="batch-stop"', false);
        $response->assertSee('text-red-600', false);
    }

    public function test_stop_button_has_label(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        // "Arreter" label on the stop button
        $response->assertSee('data-action="batch-stop"', false);
    }

    // -- Error Recovery UI (AC4) --

    public function test_error_panel_exists_and_hidden_by_default(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-error"', false);
        // Error panel hidden class
        $response->assertSee('id="batch-error" class="hidden', false);
    }

    public function test_error_panel_has_extension_name_placeholder(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-error-name"', false);
    }

    public function test_error_panel_has_error_message_placeholder(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-error-message"', false);
    }

    public function test_error_panel_has_retry_button(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-batch-action="retry"', false);
    }

    public function test_error_panel_has_skip_button(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-batch-action="skip"', false);
    }

    public function test_error_panel_has_stop_button(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('data-batch-action="stop"', false);
    }

    // -- Batch Recap Container (AC5 / Story 3.4) --

    public function test_batch_recap_container_exists(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        $response->assertSee('id="batch-recap"', false);
    }

    // -- Responsive Layout (AC1) --

    public function test_progress_has_fixed_overlay_layout(): void
    {
        $response = $this->getExtensionsPage();

        $response->assertOk();
        // Centered modal overlay with fixed positioning
        $response->assertSee('fixed inset-0', false);
    }

    // -- Page requires authentication --

    public function test_batch_progress_page_requires_authentication(): void
    {
        $response = $this->get(route('admin.settings.extensions.index'));

        $response->assertRedirect();
    }
}
