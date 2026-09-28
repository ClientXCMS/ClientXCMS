<?php

namespace Tests\Feature\Billing;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Listeners\Store\Basket\CouponUsageListener;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CouponMaxUsesRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_paid_discount_is_counted_and_overflow_is_flagged(): void
    {
        Log::spy();
        $coupon = $this->createCoupon(['code' => 'RACE_'.uniqid(), 'max_uses' => 5, 'usages' => 4, 'is_global' => true]);

        $this->complete($this->paidInvoiceWith($coupon));
        $this->complete($this->paidInvoiceWith($coupon));

        $this->assertSame(6, (int) $coupon->fresh()->usages, 'the counter must reflect every discount actually paid');
        $this->assertSame(2, CouponUsage::where('coupon_id', $coupon->id)->count());
        Log::shouldHaveReceived('warning')->once();
    }

    private function complete(Invoice $invoice): void
    {
        (new CouponUsageListener)->handle(new InvoiceCompleted($invoice));
    }

    private function paidInvoiceWith(Coupon $coupon): Invoice
    {
        $invoice = Invoice::factory()->create(['customer_id' => $this->createCustomerModel()->id, 'status' => Invoice::STATUS_PAID]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'discount' => ['id' => $coupon->id, 'code' => $coupon->code, 'type' => 'percent', 'value_price' => 10, 'value_setup' => 0, 'sub_price' => 1, 'sub_setup' => 0],
        ]);

        return $invoice->fresh();
    }
}
