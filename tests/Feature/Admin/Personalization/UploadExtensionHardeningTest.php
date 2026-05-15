<?php

namespace Tests\Feature\Admin\Personalization;

use Tests\TestCase;

class UploadExtensionHardeningTest extends TestCase
{
    private function methodSource(string $class, string $method): string
    {
        $reflection = new \ReflectionMethod($class, $method);
        $file = file($reflection->getFileName());

        return implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }

    public function test_seo_og_image_uses_guess_extension_not_client_extension(): void
    {
        $src = $this->methodSource(
            \App\Http\Controllers\Admin\Personalization\SettingsPersonalizationController::class,
            'storeSeoSettings'
        );
        $this->assertStringContainsString('->guessExtension()', $src);
        $this->assertStringNotContainsString("seo_og_image')->getClientOriginalExtension", $src);
        $this->assertStringContainsString('mimes:jpeg,png,jpg,gif,webp', $src);
    }

    public function test_theme_home_image_uses_guess_extension(): void
    {
        $src = $this->methodSource(
            \App\Http\Controllers\Admin\Personalization\SettingsPersonalizationController::class,
            'storeHomeSettings'
        );
        $this->assertStringContainsString('->guessExtension()', $src);
        $this->assertStringNotContainsString("theme_home_image')->getClientOriginalExtension", $src);

        $src2 = $this->methodSource(
            \App\Http\Controllers\Admin\Personalization\ThemeController::class,
            'configTheme'
        );
        $this->assertStringContainsString('->guessExtension()', $src2);
    }

    public function test_group_and_product_uploads_use_guess_extension(): void
    {
        foreach ([
            \App\Http\Requests\Store\StoreGroupRequest::class,
            \App\Http\Requests\Store\UpdateGroupRequest::class,
            \App\Http\Requests\Store\StoreProductRequest::class,
            \App\Http\Requests\Store\UpdateProductRequest::class,
        ] as $class) {
            $reflection = new \ReflectionClass($class);
            $src = file_get_contents($reflection->getFileName());
            $this->assertStringContainsString('->guessExtension()', $src, "{$class} must derive the extension from the MIME, not from the client filename");
            $this->assertStringNotContainsString("file('image')->getClientOriginalExtension", $src, "{$class} must not trust the client extension");
        }
    }
}
