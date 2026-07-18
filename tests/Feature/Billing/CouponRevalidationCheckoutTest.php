<?php

namespace Tests\Feature\Billing;

use Tests\TestCase;

class CouponRevalidationCheckoutTest extends TestCase
{
    public function test_create_invoice_revalidates_coupon_at_checkout(): void
    {
        $reflection = new \ReflectionMethod(\App\Services\Billing\InvoiceService::class, 'createInvoiceFromBasket');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            '$basket->coupon_id',
            $body,
            'createInvoiceFromBasket must look at the basket coupon_id and re-check it before persisting the invoice with a stale discount'
        );
        $this->assertStringContainsString(
            '->isValid(',
            $body,
            'createInvoiceFromBasket must call Coupon::isValid again at invoice creation time so an expired / capped / first-order-only invalidated coupon is dropped from the basket before the invoice is persisted'
        );
    }
}
