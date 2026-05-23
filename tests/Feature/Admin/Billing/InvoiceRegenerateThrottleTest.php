<?php

namespace Tests\Feature\Admin\Billing;

use Tests\TestCase;

class InvoiceRegenerateThrottleTest extends TestCase
{
    public function test_invoice_regenerate_pdf_route_has_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('admin.invoices.regenerate_pdf');
        $this->assertNotNull($route);
        $middleware = collect($route->gatherMiddleware());
        $hasThrottle = $middleware->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));
        $this->assertTrue(
            $hasThrottle,
            'invoices.regenerate_pdf must declare a throttle so a hostile admin cannot loop dompdf and OOM the pod (which on a SaaS deploy takes down every co-tenant on the pod)'
        );
    }
}
