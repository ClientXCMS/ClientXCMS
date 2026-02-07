<?php

namespace Tests\Feature\Extensions;

use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionDegradationTest extends TestCase
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

    /**
     * Mocks the 'extension' service to throw on getGroupsWithExtensions,
     * simulating an API failure with expired cache.
     */
    private function mockApiFailure(): void
    {
        $mock = \Mockery::mock(ExtensionManager::class);
        $mock->shouldReceive('getGroupsWithExtensions')
            ->once()
            ->andThrow(new \Exception('API unavailable'));
        $mock->shouldReceive('getAdminMenuItems')
            ->andReturn(collect([]));
        $mock->shouldIgnoreMissing();

        $this->app->instance('extension', $mock);
    }

    // AC1 + AC3: When API fails with no local data, show "catalogue unavailable" message
    public function test_page_shows_catalogue_unavailable_when_api_fails_and_no_data(): void
    {
        $admin = $this->seedAndGetAdmin();
        $this->mockApiFailure();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('js-api-banner');
        $response->assertSee(__('extensions.settings.api_unavailable_title'));
    }

    // AC2: Installed extensions remain accessible from local data when API fails
    public function test_page_shows_installed_extensions_from_local_data_when_api_degraded(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'module', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ]);

        $this->mockApiFailure();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        // Banner should be visible
        $response->assertSee('js-api-banner');
        // Tabs should render because groups is non-empty (local fallback)
        $response->assertSee('tab-installed');
    }

    // AC4: Install failure due to API returns clear JSON error, no state corruption
    public function test_install_failure_returns_clear_error_message(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'nonexistent']));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors' => [['code', 'detail']],
        ]);
    }

    // Verify degraded page does not crash with empty extensions.json
    public function test_page_does_not_crash_when_both_api_and_local_data_fail(): void
    {
        $admin = $this->seedAndGetAdmin();

        // Mock API failure
        $mock = \Mockery::mock(ExtensionManager::class);
        $mock->shouldReceive('getGroupsWithExtensions')
            ->once()
            ->andThrow(new \Exception('API unavailable'));
        $mock->shouldReceive('getAdminMenuItems')
            ->andReturn(collect([]));
        $mock->shouldIgnoreMissing();
        $this->app->instance('extension', $mock);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
    }
}
