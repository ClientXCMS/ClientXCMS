<?php

namespace Tests\Feature\Billing;

use App\Models\Store\Basket\Basket;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponRevalidationCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_invoice_drops_a_coupon_that_expired_after_it_was_applied(): void
    {
        $coupon = $this->createCoupon(['code' => 'EXPIRED_'.uniqid(), 'is_global' => true, 'end_at' => now()->subMinute()]);
        $basket = $this->createBasketForCustomer($this->createCustomerModel());
        $basket->rows()->create(['product_id' => $this->createProductModel('active', 10)->id, 'quantity' => 1, 'billing' => 'monthly', 'currency' => 'USD']);
        $basket->update(['coupon_id' => $coupon->id]);

        $invoice = InvoiceService::createInvoiceFromBasket(Basket::find($basket->id), $this->createGatewayModel());

        $this->assertNull($basket->fresh()->coupon_id);
        $this->assertNull($invoice->items()->first()->couponId());
        $this->assertEqualsWithDelta(10.0, (float) $invoice->subtotal, 0.01);
    }
}
