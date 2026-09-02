<?php

namespace Tests\Unit\Theme;

use App\DTO\Core\Extensions\ExtensionThemeDTO;
use App\Theme\ThemeManager;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class ThemeManagerTest extends TestCase
{
    public function test_unique_enabled_theme_wins_over_stale_default_setting(): void
    {
        $resolved = $this->resolve('default', ['altura']);

        $this->assertSame('altura', $resolved->uuid);
    }

    public function test_valid_setting_is_used_when_no_theme_is_enabled(): void
    {
        $resolved = $this->resolve('voltaris', []);

        $this->assertSame('voltaris', $resolved->uuid);
    }

    public function test_default_is_the_last_fallback(): void
    {
        $resolved = $this->resolve(null, []);

        $this->assertSame('default', $resolved->uuid);
    }

    public function test_translation_namespace_is_deferred_until_application_has_booted(): void
    {
        $previousContainer = Container::getInstance();
        $previousFacadeApplication = Facade::getFacadeApplication();
        $application = new Application(dirname(__DIR__, 3));
        $application->instance('files', new Filesystem);
        Container::setInstance($application);
        Facade::setFacadeApplication($application);

        // The namespace is only registered for a theme that ships a lang directory
        $themePath = sys_get_temp_dir().'/ctx-theme-'.bin2hex(random_bytes(4));
        mkdir($themePath.'/lang', 0755, true);

        try {
            $theme = new ExtensionThemeDTO;
            $theme->uuid = 'altura';
            $theme->path = $themePath;
            $manager = (new \ReflectionClass(ThemeManager::class))->newInstanceWithoutConstructor();
            (new ReflectionProperty(ThemeManager::class, 'theme'))->setValue($manager, $theme);

            (new ReflectionMethod(ThemeManager::class, 'registerTranslations'))->invoke($manager);

            $this->assertFalse($application->bound('translator'));

            $translator = new class
            {
                public array $namespaces = [];

                public function addNamespace(string $namespace, string $path): void
                {
                    $this->namespaces[$namespace] = $path;
                }
            };
            $application->instance('translator', $translator);
            $application->boot();

            $this->assertSame($theme->path.'/lang', $translator->namespaces['theme']);
        } finally {
            Container::setInstance($previousContainer);
            Facade::setFacadeApplication($previousFacadeApplication);
            @rmdir($themePath.'/lang');
            @rmdir($themePath);
        }
    }
    private function resolve(?string $configuredTheme, array $enabledThemes): ExtensionThemeDTO
    {
            $theme->uuid = $uuid;

            return $theme;
        }, ['default', 'altura', 'voltaris']);

        $manager = (new \ReflectionClass(ThemeManager::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ThemeManager::class, 'resolveCurrentTheme');

        return $method->invoke($manager, $themes, $configuredTheme, $enabledThemes);
    }
}
