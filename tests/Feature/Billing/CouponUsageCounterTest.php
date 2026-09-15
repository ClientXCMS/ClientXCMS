<?php

namespace Tests\Feature\Billing;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Listeners\Store\Basket\CouponUsageListener;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponUsageCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_refused_usage_does_not_leave_the_global_counter_inflated(): void
    {
        $customer = Customer::factory()->create();
        $coupon = $this->coupon(['max_uses_per_customer' => 1, 'usages' => 1]);
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'customer_id' => $customer->id,
            'used_at' => now(),
            'amount' => 5,
        ]);

        $this->complete($this->discountedInvoice($customer, $coupon));

        $this->assertSame(1, (int) $coupon->fresh()->usages, 'a usage that was refused must not stay counted in the global total');
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count(), 'no extra usage row must be recorded');
    }

    public function test_an_accepted_usage_still_counts(): void
    {
        $customer = Customer::factory()->create();
        $coupon = $this->coupon(['max_uses_per_customer' => 1, 'usages' => 0]);

        $this->complete($this->discountedInvoice($customer, $coupon));

        $this->assertSame(1, (int) $coupon->fresh()->usages, 'an accepted usage must be counted');
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
    }

    private function complete(Invoice $invoice): void
    {
        (new CouponUsageListener)->handle(new InvoiceCompleted($invoice));
    }

    private function coupon(array $attributes): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'USAGE_COUNTER_'.uniqid(),
            'type' => 'fixed',
            'applied_month' => 1,
            'free_setup' => false,
            'first_order_only' => false,
            'max_uses' => 0,
            'max_uses_per_customer' => 0,
            'usages' => 0,
            'unique_use' => 0,
            'products_required' => [],
            'is_global' => true,
            'minimum_order_amount' => 0,
        ], $attributes));
    }

    private function discountedInvoice(Customer $customer, Coupon $coupon): Invoice
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PENDING,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'unit_price_ht' => 100.0,
            'unit_price_ttc' => 100.0,
            'quantity' => 1,
            'discount' => json_encode([
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => 'fixed',
                'value_price' => 5,
                'value_setup' => 0,
                'sub_price' => 5,
                'sub_setup' => 0,
            ]),
        ]);

        return $invoice->fresh();
    }
}
