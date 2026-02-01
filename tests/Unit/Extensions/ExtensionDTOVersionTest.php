<?php

namespace Tests\Unit\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use Tests\TestCase;

class ExtensionDTOVersionTest extends TestCase
{
    public function test_get_latest_version_returns_api_latest_version_when_set(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'test-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.0.0',
            'api' => [
                'version' => '2.0.0',
                'latest_version' => '2.0.0',
            ],
        ]);

        $this->assertEquals('2.0.0', $dto->getLatestVersion());
    }

    public function test_get_latest_version_falls_back_to_api_version(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'test-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.0.0',
            'api' => [
                'version' => '2.0.0',
            ],
        ]);

        $this->assertEquals('2.0.0', $dto->getLatestVersion());
    }

    public function test_get_latest_version_returns_null_when_no_version_in_api(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'test-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.0.0',
            'api' => [],
        ]);

        $this->assertNull($dto->getLatestVersion());
    }

    public function test_get_latest_version_returns_installed_version_for_unofficial(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'custom-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.5.0',
            'api' => [
                'unofficial' => true,
                'version' => '9.9.9',
            ],
        ]);

        // Unofficial extensions return the installed version, not the api version
        $this->assertEquals('1.5.0', $dto->getLatestVersion());
    }

    public function test_get_latest_version_prefers_latest_version_over_api_version(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'test-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.0.0',
            'api' => [
                'version' => '1.5.0',
                'latest_version' => '2.0.0',
            ],
        ]);

        // latest_version takes precedence over api version
        $this->assertEquals('2.0.0', $dto->getLatestVersion());
    }

    public function test_installed_version_differs_from_latest_version(): void
    {
        $dto = ExtensionDTO::fromArray([
            'uuid' => 'outdated-mod',
            'type' => 'module',
            'enabled' => true,
            'version' => '1.0.0',
            'api' => [
                'latest_version' => '2.0.0',
            ],
        ]);

        $this->assertEquals('1.0.0', $dto->version);
        $this->assertEquals('2.0.0', $dto->getLatestVersion());
        $this->assertNotEquals($dto->version, $dto->getLatestVersion());
    }
}
