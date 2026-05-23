<?php

namespace Tests\Feature\Admin\Store;

use Tests\TestCase;

class ProductDescriptionXssTest extends TestCase
{
    public function test_store_product_request_applies_no_script_rule(): void
    {
        $src = file_get_contents(app_path('Http/Requests/Store/StoreProductRequest.php'));
        $this->assertMatchesRegularExpression(
            '/[\'"]description[\'"]\s*=>\s*\[[^\]]*new\s+\\\\?App\\\\Rules\\\\NoScriptOrPhpTags/',
            $src,
            'StoreProductRequest must apply NoScriptOrPhpTags on description'
        );
    }

    public function test_update_product_request_applies_no_script_rule(): void
    {
        $src = file_get_contents(app_path('Http/Requests/Store/UpdateProductRequest.php'));
        $this->assertMatchesRegularExpression(
            '/[\'"]description[\'"]\s*=>\s*\[[^\]]*new\s+\\\\?App\\\\Rules\\\\NoScriptOrPhpTags/',
            $src,
            'UpdateProductRequest must apply NoScriptOrPhpTags on description'
        );
    }

    public function test_no_script_rule_actually_rejects_script_tag(): void
    {
        $rule = new \App\Rules\NoScriptOrPhpTags;
        $this->assertFalse($rule->passes('description', '<script>alert(1)</script>'));
        $this->assertFalse($rule->passes('description', '<?php phpinfo();'));
    }

    public function test_invoice_show_tab_escapes_item_description(): void
    {
        $src = file_get_contents(resource_path('views/admin/core/invoices/tabs/show.blade.php'));
        $this->assertStringNotContainsString('nl2br($item->description)', $src);
        $this->assertStringContainsString('nl2br(e($item->description))', $src);
    }
}
