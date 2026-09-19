<?php

namespace Tests\Unit\DTO;

use App\DTO\Core\Extensions\ExtensionDTO;
use Tests\TestCase;

class ExtensionDTOTest extends TestCase
{
    private function extension(bool $installed, ?string $version, ?string $latestVersion): ExtensionDTO
    {
        $extension = new ExtensionDTO('demo', 'theme', true, $latestVersion === null ? [] : ['version' => $latestVersion], $version);
        $reflection = new \ReflectionProperty(ExtensionDTO::class, 'installed');
        $reflection->setAccessible(true);
        $reflection->setValue($extension, $installed);

        return $extension;
    }

    public function test_it_never_flags_an_update_when_not_installed(): void
    {
        $extension = $this->extension(installed: false, version: '1.0', latestVersion: '2.0');

        $this->assertFalse($extension->hasUpdateAvailable());
    }

    public function test_it_never_flags_an_update_when_the_remote_catalogue_has_no_version(): void
    {
        $extension = $this->extension(installed: true, version: '1.0', latestVersion: null);

        $this->assertFalse($extension->hasUpdateAvailable());
    }

    public function test_it_flags_an_update_when_the_local_version_is_older(): void
    {
        $extension = $this->extension(installed: true, version: '1.0', latestVersion: '2.0');

        $this->assertTrue($extension->hasUpdateAvailable());
    }

    public function test_it_does_not_flag_an_update_when_already_current(): void
    {
        $extension = $this->extension(installed: true, version: '2.0', latestVersion: '2.0');

        $this->assertFalse($extension->hasUpdateAvailable());
    }

    public function test_an_unknown_local_version_defaults_to_flagging_an_update_without_triggering_a_deprecation(): void
    {
        // A locally installed extension that was never registered (e.g.
        // cloned in by a dev tool instead of going through the app's
        // install flow) has version === null: we can't prove it's up to
        // date, so this must default to true - and it must never do that by
        // feeding null into version_compare(), which raises a PHP
        // deprecation warning on every render since PHP 8.1.
        $extension = $this->extension(installed: true, version: null, latestVersion: '1.3');

        $deprecations = [];
        set_error_handler(function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_DEPRECATED);

        try {
            $result = $extension->hasUpdateAvailable();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($result);
        $this->assertSame([], $deprecations, 'version_compare() must never be called with a null argument');
    }
}
