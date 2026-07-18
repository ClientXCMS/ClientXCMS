<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;

class SettingsRenderingXssTest extends TestCase
{
    public static function viewProvider(): array
    {
        return [
            ['resources/views/admin/core/invoices/tabs/show.blade.php'],
            ['resources/themes/default/views/front/billing/invoices/pdf.blade.php'],
            ['resources/themes/default/views/front/billing/invoices/show.blade.php'],
        ];
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_nl2br_setting_is_escaped(string $relativePath): void
    {
        $src = file_get_contents(base_path($relativePath));
        $this->assertDoesNotMatchRegularExpression(
            '/\{!!\s*nl2br\(\s*setting\(/',
            $src,
            "{$relativePath} renders an admin-controllable setting through nl2br without e() - stored XSS"
        );
    }
}
