<?php

namespace Tests\Unit\Extensions;

use App\DTO\Core\Extensions\ExtensionThemeDTO;
use App\Extensions\ExtensionManager;
use Tests\TestCase;

class ExtensionManagerVersionResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ExtensionManager::writeExtensionJson([]);
    }

    protected function tearDown(): void
    {
        ExtensionManager::writeExtensionJson([]);
        parent::tearDown();
    }

    private function fakeTheme(string $uuid, string $version): void
    {
        app()->instance('theme', new class($uuid, $version)
        {
            public function __construct(private string $uuid, private string $version) {}

            public function getTheme(): ExtensionThemeDTO
            {
                $theme = new ExtensionThemeDTO;
                $theme->uuid = $this->uuid;
                $theme->version = $this->version;

                return $theme;
            }
        });
    }

    /**
     * ExtensionManager::fetch() builds its own `new CacheManager(app())`
     * instead of resolving the app's cache singleton (ExtensionManager.php:103)
     * - fine against a shared backend like Redis, but with the 'array' driver
     * used in tests, every instance gets its own isolated in-memory store
     * (Illuminate\Cache\ArrayStore::$storage is a plain instance property),
     * so Cache::put() from a test is never visible to it. Setting the
     * private $extensions property directly is the only way to feed
     * getAllExtensions() a controlled remote catalogue without also fixing
     * that separate, pre-existing issue.
     */
    private function withRemoteCatalogue(array $items): ExtensionManager
    {
        $manager = new ExtensionManager;
        (function () use ($items) {
            $this->extensions = ['items' => $items];
        })->call($manager);

        return $manager;
    }

    public function test_an_extension_missing_from_the_local_registry_reports_no_version_instead_of_a_strangers(): void
    {
        // "ghost-theme" is only known to the remote catalogue, never
        // registered locally - exactly what happens when a theme is cloned
        // straight into resources/themes/ by a dev tool instead of being
        // installed through the app.
        ExtensionManager::writeExtensionJson([
            'themes' => [
                ['uuid' => 'known-theme', 'version' => '1.4', 'type' => 'themes', 'enabled' => false, 'installed' => true],
            ],
        ]);
        $this->fakeTheme('known-theme', '1.4');
        $manager = $this->withRemoteCatalogue([
            ['uuid' => 'known-theme', 'type' => 'theme', 'version' => '1.4'],
            ['uuid' => 'ghost-theme', 'type' => 'theme', 'version' => '1.3'],
        ]);

        $extensions = $manager->getAllExtensions(withTheme: true, withUnofficial: false);

        $ghost = $extensions->first(fn ($e) => $e->uuid === 'ghost-theme');
        $this->assertNotNull($ghost);
        $this->assertNull($ghost->version, "an unregistered extension must report no version, never a stranger's");
    }

    public function test_the_active_theme_reports_its_own_version_even_when_not_yet_in_the_local_registry(): void
    {
        // The active theme's version used to be appended to the version list
        // without its uuid being appended to the uuid list alongside it - any
        // index drift between the two made it read a neighbour's version.
        ExtensionManager::writeExtensionJson([
            'themes' => [
                ['uuid' => 'known-theme', 'version' => '1.4', 'type' => 'themes', 'enabled' => false, 'installed' => true],
            ],
        ]);
        $this->fakeTheme('active-theme', '2.0');
        $manager = $this->withRemoteCatalogue([
            ['uuid' => 'known-theme', 'type' => 'theme', 'version' => '1.4'],
            ['uuid' => 'active-theme', 'type' => 'theme', 'version' => '2.0'],
        ]);

        $extensions = $manager->getAllExtensions(withTheme: true, withUnofficial: false);

        $active = $extensions->first(fn ($e) => $e->uuid === 'active-theme');
        $this->assertNotNull($active);
        $this->assertSame(
            '2.0',
            $active->version,
            "the active theme must report its own version, not a neighbour's picked up by index drift"
        );
    }
}
