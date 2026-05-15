<?php

namespace Tests\Feature\Admin\Billing;

use Tests\TestCase;

class InvoiceItemDescriptionXssTest extends TestCase
{
    public function test_admin_fulfillment_view_escapes_item_description(): void
    {
        $src = file_get_contents(resource_path('views/admin/core/invoices/tabs/fulfillment.blade.php'));
        $this->assertStringNotContainsString('nl2br($item->description)', $src);
        $this->assertStringContainsString('nl2br(e($item->description))', $src);
    }

    public function test_default_theme_invoice_show_escapes_item_description(): void
    {
        $src = file_get_contents(base_path('resources/themes/default/views/front/billing/invoices/show.blade.php'));
        $this->assertStringNotContainsString('nl2br($item->description)', $src);
        $this->assertStringContainsString('nl2br(e($item->description))', $src);
    }
}
