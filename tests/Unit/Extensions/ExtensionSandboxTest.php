<?php

namespace Tests\Unit\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Extensions\ExtensionSandbox;
use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionSandboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_autoload_returns_true(): void
    {
        $manager = new class implements \App\Extensions\ExtensionInterface {
            public bool $called = false;
            public function autoload(ExtensionDTO $DTO, \Illuminate\Foundation\Application $app, ClassLoader $composer): void {
                $this->called = true;
            }
            public function getExtensions(bool $enabledOnly = false): array { return []; }
        };

        $dto = ExtensionDTO::fromArray([
            'uuid' => 'sandbox-fixture-ok',
            'type' => 'module',
            'enabled' => true,
            'installed' => true,
            'version' => '1.0.0',
        ]);

        $ok = ExtensionSandbox::autoload(
            $manager,
            $dto,
            app(),
            require base_path('vendor/autoload.php')
        );

        $this->assertTrue($ok);
        $this->assertTrue($manager->called);
    }

    public function test_throwing_extension_is_caught_and_disabled(): void
    {
        $manager = new class implements \App\Extensions\ExtensionInterface {
            public function autoload(ExtensionDTO $DTO, \Illuminate\Foundation\Application $app, ClassLoader $composer): void {
                throw new \RuntimeException('boom from extension');
            }
            public function getExtensions(bool $enabledOnly = false): array { return []; }
        };

        // Seed an entry in extensions.json so the sandbox has something to flip.
        $cache = \App\Extensions\ExtensionManager::readExtensionJson();
        $cache['modules'][] = [
            'uuid' => 'sandbox-fixture-broken',
            'type' => 'module',
            'enabled' => true,
            'installed' => true,
        ];
        \App\Extensions\ExtensionManager::writeExtensionJson($cache);

        $dto = ExtensionDTO::fromArray([
            'uuid' => 'sandbox-fixture-broken',
            'type' => 'module',
            'enabled' => true,
            'installed' => true,
            'version' => '1.0.0',
        ]);

        $ok = ExtensionSandbox::autoload(
            $manager,
            $dto,
            app(),
            require base_path('vendor/autoload.php')
        );

        $this->assertFalse($ok);

        $cache = \App\Extensions\ExtensionManager::readExtensionJson();
        $row = collect($cache['modules'] ?? [])->firstWhere('uuid', 'sandbox-fixture-broken');
        $this->assertNotNull($row);
        $this->assertFalse((bool) ($row['enabled'] ?? true), 'broken extension should have been disabled');
        $this->assertArrayHasKey('boot_error', $row);
        $this->assertStringContainsString('boom', $row['boot_error']['message']);
    }

    public function test_safe_boot_mode_skips_all_extensions(): void
    {
        $manager = new class implements \App\Extensions\ExtensionInterface {
            public bool $called = false;
            public function autoload(ExtensionDTO $DTO, \Illuminate\Foundation\Application $app, ClassLoader $composer): void {
                $this->called = true;
            }
            public function getExtensions(bool $enabledOnly = false): array { return []; }
        };
        $dto = ExtensionDTO::fromArray(['uuid' => 'x', 'type' => 'module', 'enabled' => true, 'installed' => true, 'version' => '1.0.0']);

        $originalSafeBoot = $_ENV['APP_SAFE_BOOT'] ?? null;
        putenv('APP_SAFE_BOOT=true');
        $_ENV['APP_SAFE_BOOT'] = 'true';
        try {
            $ok = ExtensionSandbox::autoload($manager, $dto, app(), require base_path('vendor/autoload.php'));
            $this->assertFalse($ok);
            $this->assertFalse($manager->called, 'safe boot must not invoke autoload');
        } finally {
            if ($originalSafeBoot === null) {
                putenv('APP_SAFE_BOOT');
                unset($_ENV['APP_SAFE_BOOT']);
            } else {
                putenv('APP_SAFE_BOOT=' . $originalSafeBoot);
                $_ENV['APP_SAFE_BOOT'] = $originalSafeBoot;
            }
        }
    }
}
