<?php

namespace Tests\Unit\Extensions;

use App\Extensions\ExtensionManager;
use Tests\TestCase;

/**
 * Tests for ExtensionManager state read/write operations.
 *
 * In-memory tests: writeExtensionJson/readExtensionJson use an in-memory
 * array (self::$testExtensions) in testing environment for data logic tests.
 *
 * Filesystem tests: writeExtensionJsonToFile is tested directly with temp
 * directories to validate the atomic write pattern (backup, temp file, rename).
 */
class ExtensionStateWriterTest extends TestCase
{
    private array $tempDirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        ExtensionManager::writeExtensionJson([]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                array_map('unlink', glob($dir.'/*'));
                rmdir($dir);
            }
        }
        parent::tearDown();
    }

    public function test_write_and_read_extensions_json(): void
    {
        $extensions = [
            'modules' => [
                ['uuid' => 'test-module', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];

        ExtensionManager::writeExtensionJson($extensions);
        $result = ExtensionManager::readExtensionJson();

        $this->assertEquals($extensions, $result);
    }

    public function test_write_empty_extensions(): void
    {
        ExtensionManager::writeExtensionJson([]);
        $result = ExtensionManager::readExtensionJson();

        $this->assertEquals([], $result);
    }

    public function test_write_preserves_all_extension_types(): void
    {
        $extensions = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
            'addons' => [
                ['uuid' => 'addon-1', 'version' => '2.0.0', 'type' => 'addons', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
            'themes' => [
                ['uuid' => 'theme-1', 'version' => '1.0.0', 'type' => 'themes', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
            'email_templates' => [
                ['uuid' => 'email-1', 'version' => '1.0.0', 'type' => 'email_templates', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];

        ExtensionManager::writeExtensionJson($extensions);
        $result = ExtensionManager::readExtensionJson();

        $this->assertArrayHasKey('modules', $result);
        $this->assertArrayHasKey('addons', $result);
        $this->assertArrayHasKey('themes', $result);
        $this->assertArrayHasKey('email_templates', $result);
        $this->assertCount(1, $result['modules']);
        $this->assertCount(1, $result['addons']);
        $this->assertEquals('mod-1', $result['modules'][0]['uuid']);
        $this->assertEquals('addon-1', $result['addons'][0]['uuid']);
    }

    public function test_write_overwrites_previous_state(): void
    {
        $initial = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJson($initial);

        $updated = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJson($updated);

        $result = ExtensionManager::readExtensionJson();
        $this->assertFalse($result['modules'][0]['enabled']);
    }

    public function test_read_returns_empty_array_when_no_data(): void
    {
        ExtensionManager::writeExtensionJson([]);
        $result = ExtensionManager::readExtensionJson();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_enable_extension_state(): void
    {
        $extensions = [
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJson($extensions);

        $result = ExtensionManager::readExtensionJson();
        $this->assertFalse($result['modules'][0]['enabled']);

        $extensions['modules'][0]['enabled'] = true;
        ExtensionManager::writeExtensionJson($extensions);

        $result = ExtensionManager::readExtensionJson();
        $this->assertTrue($result['modules'][0]['enabled']);
    }

    public function test_disable_extension_state(): void
    {
        $extensions = [
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJson($extensions);

        $extensions['modules'][0]['enabled'] = false;
        ExtensionManager::writeExtensionJson($extensions);

        $result = ExtensionManager::readExtensionJson();
        $this->assertFalse($result['modules'][0]['enabled']);
    }

    public function test_write_with_complex_api_data(): void
    {
        $extensions = [
            'modules' => [
                [
                    'uuid' => 'complex-mod',
                    'version' => '1.2.3',
                    'type' => 'modules',
                    'enabled' => true,
                    'installed' => true,
                    'api' => [
                        'name' => 'Complex Module',
                        'description' => 'A test module',
                        'version' => '1.5.0',
                        'latest_version' => '1.5.0',
                        'author' => ['name' => 'Test Author'],
                    ],
                ],
            ],
        ];

        ExtensionManager::writeExtensionJson($extensions);
        $result = ExtensionManager::readExtensionJson();

        $this->assertEquals('Complex Module', $result['modules'][0]['api']['name']);
        $this->assertEquals('1.5.0', $result['modules'][0]['api']['latest_version']);
        $this->assertEquals('1.2.3', $result['modules'][0]['version']);
    }

    public function test_multiple_extensions_in_same_type(): void
    {
        $extensions = [
            'addons' => [
                ['uuid' => 'addon-a', 'version' => '1.0.0', 'type' => 'addons', 'enabled' => true, 'installed' => true, 'api' => []],
                ['uuid' => 'addon-b', 'version' => '2.0.0', 'type' => 'addons', 'enabled' => false, 'installed' => true, 'api' => []],
                ['uuid' => 'addon-c', 'version' => '3.0.0', 'type' => 'addons', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];

        ExtensionManager::writeExtensionJson($extensions);
        $result = ExtensionManager::readExtensionJson();

        $this->assertCount(3, $result['addons']);
        $this->assertEquals('addon-a', $result['addons'][0]['uuid']);
        $this->assertEquals('addon-b', $result['addons'][1]['uuid']);
        $this->assertEquals('addon-c', $result['addons'][2]['uuid']);
    }

    public function test_concurrent_writes_last_wins(): void
    {
        $stateA = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        $stateB = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '2.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ];

        ExtensionManager::writeExtensionJson($stateA);
        ExtensionManager::writeExtensionJson($stateB);

        $result = ExtensionManager::readExtensionJson();
        $this->assertEquals('2.0.0', $result['modules'][0]['version']);
        $this->assertFalse($result['modules'][0]['enabled']);
    }

    public function test_write_preserves_api_latest_version_separately(): void
    {
        $extensions = [
            'modules' => [
                [
                    'uuid' => 'versioned-mod',
                    'version' => '1.0.0',
                    'type' => 'modules',
                    'enabled' => true,
                    'installed' => true,
                    'api' => [
                        'version' => '2.0.0',
                        'latest_version' => '2.0.0',
                    ],
                ],
            ],
        ];

        ExtensionManager::writeExtensionJson($extensions);
        $result = ExtensionManager::readExtensionJson();

        // Installed version and API latest version must be distinct
        $this->assertEquals('1.0.0', $result['modules'][0]['version']);
        $this->assertEquals('2.0.0', $result['modules'][0]['api']['latest_version']);
    }

    // --- Filesystem atomic write tests (bypass testing environment) ---

    private function createTempDir(): string
    {
        $dir = sys_get_temp_dir().'/ext_test_'.uniqid();
        mkdir($dir, 0755, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    public function test_atomic_write_creates_backup_file(): void
    {
        $dir = $this->createTempDir();

        $initial = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJsonToFile($initial, $dir);

        $this->assertFileExists($dir.'/extensions.json');
        $this->assertFileDoesNotExist($dir.'/extensions.json.bak');

        $updated = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '2.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJsonToFile($updated, $dir);

        $this->assertFileExists($dir.'/extensions.json.bak');

        // Backup contains the initial state
        $bakContent = json_decode(file_get_contents($dir.'/extensions.json.bak'), true);
        $this->assertEquals('1.0.0', $bakContent['modules'][0]['version']);

        // Current file has the updated state
        $currentContent = json_decode(file_get_contents($dir.'/extensions.json'), true);
        $this->assertEquals('2.0.0', $currentContent['modules'][0]['version']);
    }

    public function test_atomic_write_removes_temp_file_after_success(): void
    {
        $dir = $this->createTempDir();

        $extensions = [
            'modules' => [
                ['uuid' => 'mod-1', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ];
        ExtensionManager::writeExtensionJsonToFile($extensions, $dir);

        $this->assertFileDoesNotExist($dir.'/extensions.json.tmp');
        $this->assertFileExists($dir.'/extensions.json');
    }

    public function test_atomic_write_produces_valid_json(): void
    {
        $dir = $this->createTempDir();

        $extensions = [
            'modules' => [
                [
                    'uuid' => 'mod-1',
                    'version' => '1.0.0',
                    'type' => 'modules',
                    'enabled' => true,
                    'installed' => true,
                    'api' => ['name' => 'Test Module'],
                ],
            ],
        ];
        ExtensionManager::writeExtensionJsonToFile($extensions, $dir);

        $content = file_get_contents($dir.'/extensions.json');
        $decoded = json_decode($content, true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals($extensions, $decoded);
    }

    public function test_atomic_write_creates_directory_if_not_exists(): void
    {
        $dir = sys_get_temp_dir().'/ext_test_'.uniqid().'/nested';
        $this->tempDirs[] = $dir;

        $extensions = ['modules' => []];
        ExtensionManager::writeExtensionJsonToFile($extensions, $dir);

        $this->assertFileExists($dir.'/extensions.json');
    }
}
