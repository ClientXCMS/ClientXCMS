<?php

namespace Tests\Unit\Extensions;

use App\Extensions\ExtensionManager;
use Tests\TestCase;

class ExtensionManagerLocalRegistryTest extends TestCase
{
    private function upsert(array $entries, string $type, string $uuid, array $api): array
    {
        $method = new \ReflectionMethod(ExtensionManager::class, 'upsertLocalEntry');
        $method->setAccessible(true);

        return $method->invoke(null, $entries, $type, $uuid, $api);
    }

    public function test_it_updates_the_version_of_an_already_registered_extension(): void
    {
        $result = $this->upsert(
            entries: [
                ['uuid' => 'known-theme', 'version' => '1.0', 'type' => 'themes', 'enabled' => true, 'installed' => true],
            ],
            type: 'themes',
            uuid: 'known-theme',
            api: ['uuid' => 'known-theme', 'type' => 'theme', 'version' => '1.1'],
        );

        $this->assertCount(1, $result);
        $this->assertSame('1.1', $result[0]['version']);
        $this->assertTrue($result[0]['enabled'], 'updating must not reset an existing enabled flag');
    }

    public function test_it_registers_an_extension_a_successful_update_found_missing_from_the_registry(): void
    {
        // Exactly what happens when a theme was cloned straight into
        // resources/themes/ by a dev tool instead of installed through the
        // app: physically present, never registered - a successful update
        // is the natural point to self-heal that instead of leaving it
        // unregistered forever.
        $result = $this->upsert(
            entries: [
                ['uuid' => 'known-theme', 'version' => '1.0', 'type' => 'themes', 'enabled' => true, 'installed' => true],
            ],
            type: 'themes',
            uuid: 'ghost-theme',
            api: ['uuid' => 'ghost-theme', 'type' => 'theme', 'version' => '1.3'],
        );

        $this->assertCount(2, $result, 'a missing extension must be added, not silently dropped');
        $ghost = collect($result)->firstWhere('uuid', 'ghost-theme');
        $this->assertNotNull($ghost);
        $this->assertSame('1.3', $ghost['version']);
        $this->assertSame('themes', $ghost['type']);
        $this->assertTrue($ghost['installed']);
        $this->assertFalse($ghost['enabled'], 'a freshly registered extension must not be silently enabled');
    }

    public function test_it_registers_the_first_extension_of_a_type_with_no_prior_entries(): void
    {
        $result = $this->upsert(entries: [], type: 'themes', uuid: 'ghost-theme', api: ['uuid' => 'ghost-theme', 'type' => 'theme', 'version' => '1.3']);

        $this->assertCount(1, $result);
        $this->assertSame('ghost-theme', $result[0]['uuid']);
    }
}
