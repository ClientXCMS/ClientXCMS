<?php

namespace Tests\Feature\Admin\Billing;

use Tests\TestCase;

class InvoiceRegenerateCsrfTest extends TestCase
{
    public function test_regenerate_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.invoices.regenerate_pdf');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods);
        $this->assertNotContains('GET', $methods, 'regenerate_pdf must NOT accept GET (CSRF surface, would let an attacker burn server CPU regenerating PDFs)');
    }
}
