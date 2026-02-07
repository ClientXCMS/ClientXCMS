<?php

namespace Tests\Feature\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionUpdateBannerTest extends TestCase
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
     * Build a test ExtensionDTO with controllable version info.
     * Overrides the filesystem-based isInstalled() by setting the public property.
     */
    private function makeExtensionDTO(
        string $uuid,
        ?string $version,
        ?string $latestVersion = null,
        bool $installed = true,
        bool $enabled = true
    ): ExtensionDTO {
        $api = [
            'name' => ucfirst(str_replace('-', ' ', $uuid)),
            'translations' => [
                'name' => ['en' => ucfirst(str_replace('-', ' ', $uuid))],
                'short_description' => ['en' => 'Test extension'],
            ],
            'thumbnail' => 'https://example.com/thumb.png',
            'formatted_price' => 'Free',
            'price' => 0,
            'group_uuid' => 'test-group',
            'tags' => [],
        ];

        if ($latestVersion !== null) {
            $api['latest_version'] = $latestVersion;
        }

        $dto = new ExtensionDTO($uuid, 'module', $enabled, $api, $version);
        $dto->installed = $installed;

        return $dto;
    }

    /**
     * Mock the 'extension' service to return groups containing given DTOs.
     * Also mocks fetch() for the tags section used by the view.
     */
    private function mockExtensionService(array $extensions): void
    {
        $groups = [
            'Test Group' => [
                'items' => collect($extensions),
                'icon' => 'bi bi-puzzle',
            ],
        ];

        $mock = \Mockery::mock(ExtensionManager::class);
        $mock->shouldReceive('getGroupsWithExtensions')
            ->once()
            ->andReturn($groups);
        $mock->shouldReceive('fetch')
            ->andReturn(['tags' => []]);
        $mock->shouldReceive('getAdminMenuItems')
            ->andReturn(collect([]));
        $mock->shouldIgnoreMissing();

        $this->app->instance('extension', $mock);
    }

    // AC1: Amber alert banner visible when updates are available
    public function test_update_banner_shows_when_updates_available(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '1.0.0', '2.0.0'),
            $this->makeExtensionDTO('mod-b', '1.0.0', '1.0.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('update-banner');
        $response->assertSee('1 mise');
    }

    // AC1: Banner is hidden when all extensions are up-to-date
    public function test_update_banner_hidden_when_no_updates(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '2.0.0', '2.0.0'),
            $this->makeExtensionDTO('mod-b', '1.5.0', '1.5.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        // The banner text is always rendered but hidden via CSS class when no updates
        $response->assertSee('id="update-banner"', false);
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/id="update-banner"\s+class="[^"]*hidden/',
            $content,
            'Update banner must have hidden class when no updates are available'
        );
    }

    // AC5: Banner count is correct with plural agreement
    public function test_update_banner_shows_correct_plural_count(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '1.0.0', '2.0.0'),
            $this->makeExtensionDTO('mod-b', '1.0.0', '3.0.0'),
            $this->makeExtensionDTO('mod-c', '5.0.0', '5.0.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('2 mises');
    }

    // M5 fix validation: null version defaults to '0' and still triggers update
    public function test_update_banner_handles_null_version_safely(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-null', null, '2.0.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('1 mise');
    }

    // AC2: CTA button has the correct data-action attribute for JS binding
    public function test_update_banner_cta_has_correct_action_attribute(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '1.0.0', '2.0.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('data-action="add-updates-to-cart"', false);
    }

    // AC5: Banner has responsive text (desktop + mobile variants)
    public function test_update_banner_has_responsive_cta_text(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '1.0.0', '2.0.0'),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertSee('Tout mettre');
        $response->assertSee('Mettre');
    }

    // Banner should not be included when groups are empty (no extensions at all)
    public function test_update_banner_not_included_when_groups_empty(): void
    {
        $admin = $this->seedAndGetAdmin();

        $mock = \Mockery::mock(ExtensionManager::class);
        $mock->shouldReceive('getGroupsWithExtensions')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('fetch')
            ->andReturn(['tags' => []]);
        $mock->shouldReceive('getAdminMenuItems')
            ->andReturn(collect([]));
        $mock->shouldIgnoreMissing();
        $this->app->instance('extension', $mock);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        $response->assertDontSee('update-banner');
    }

    // Non-installed extensions should not be counted in update banner
    public function test_update_banner_ignores_non_installed_extensions(): void
    {
        $admin = $this->seedAndGetAdmin();

        $extensions = [
            $this->makeExtensionDTO('mod-a', '1.0.0', '2.0.0', false),
            $this->makeExtensionDTO('mod-b', '1.0.0', '3.0.0', false),
        ];

        $this->mockExtensionService($extensions);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.extensions.index'));

        $response->assertOk();
        // The banner text is always rendered but hidden via CSS class when no updates
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/id="update-banner"\s+class="[^"]*hidden/',
            $content,
            'Update banner must have hidden class when non-installed extensions have updates'
        );
    }
}
