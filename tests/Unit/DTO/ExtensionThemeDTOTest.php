<?php

namespace Tests\Unit\DTO;

use App\DTO\Core\Extensions\ExtensionThemeDTO;
use PHPUnit\Framework\TestCase;

class ExtensionThemeDTOTest extends TestCase
{
    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themePath = sys_get_temp_dir().'/clientxcms-theme-'.bin2hex(random_bytes(8));
        mkdir($this->themePath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->themePath.'/menus.json')) {
            unlink($this->themePath.'/menus.json');
        }
        rmdir($this->themePath);

        parent::tearDown();
    }

    public function test_theme_without_menus_file_does_not_support_menus(): void
    {
        $theme = $this->theme();

        $this->assertFalse($theme->supportsMenus());
        $this->assertSame([], $theme->menuTypes());
    }

    public function test_theme_exposes_valid_menu_types(): void
    {
        file_put_contents($this->themePath.'/menus.json', json_encode([
            'theme_header' => [['name' => 'Home', 'url' => '/']],
            'theme-footer' => [],
        ], JSON_THROW_ON_ERROR));

        $theme = $this->theme();

        $this->assertTrue($theme->supportsMenus());
        $this->assertSame(['theme_header', 'theme-footer'], $theme->menuTypes());
    }

    public function test_invalid_json_does_not_enable_menu_support(): void
    {
        file_put_contents($this->themePath.'/menus.json', '{invalid');

        $this->assertFalse($this->theme()->supportsMenus());
    }

    private function theme(): ExtensionThemeDTO
    {
        $theme = new ExtensionThemeDTO;
        $theme->path = $this->themePath;

        return $theme;
    }
}
