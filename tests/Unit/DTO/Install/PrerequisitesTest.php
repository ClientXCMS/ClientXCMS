<?php

namespace Tests\Unit\DTO\Install;

use App\DTO\Install\Prerequisites;
use Tests\TestCase;

class PrerequisitesTest extends TestCase
{
    public function test_required_php_version_matches_composer_constraint(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        // The installer must refuse versions composer itself would refuse (^8.3).
        $this->assertSame('^8.3', $composer['require']['php']);
        $this->assertSame('8.3.0', Prerequisites::REQUIRED_PHP_VERSION);
    }

    public function test_every_extension_required_by_composer_is_checked(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $composerExtensions = collect(array_keys($composer['require']))
            ->filter(fn (string $package) => str_starts_with($package, 'ext-'))
            ->map(fn (string $package) => substr($package, 4))
            ->values();

        $reflection = new \ReflectionProperty(Prerequisites::class, 'requiredExtensions');
        $checked = $reflection->getValue(new Prerequisites);

        foreach ($composerExtensions as $extension) {
            $this->assertContains($extension, $checked, "ext-{$extension} is required by composer but not checked by the installer");
        }
    }

    public function test_current_runtime_satisfies_prerequisites(): void
    {
        $this->assertSame([], (new Prerequisites)->errors);
    }
}
