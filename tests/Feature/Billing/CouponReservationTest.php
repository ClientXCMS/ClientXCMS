<?php

namespace Tests\Feature\Billing;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Listeners\Store\Basket\CouponUsageListener;
use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;
use App\Services\Billing\InvoiceService;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CouponReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_customer_gets_no_discount_while_the_last_use_is_held_by_a_pending_invoice(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);

        $basket = $this->basketWithCoupon($this->createCustomerModel(), $coupon);
        $invoice = $this->checkout($basket);

        $this->assertInvoiceHasNoDiscount($invoice);
        $this->assertNull($basket->fresh()->coupon_id, 'the coupon must be removed from the basket');
    }

    public function test_a_failed_invoice_still_holds_its_use(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_FAILED);

        $invoice = $this->checkout($this->basketWithCoupon($this->createCustomerModel(), $coupon));

        $this->assertInvoiceHasNoDiscount($invoice);
    }

    public function test_a_cancelled_or_deleted_invoice_releases_its_use(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_CANCELLED);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_REFUNDED);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING)->delete();

        $invoice = $this->checkout($this->basketWithCoupon($this->createCustomerModel(), $coupon));

        $this->assertInvoiceHasDiscount($invoice, $coupon);
    }

    public function test_the_basket_own_invoice_does_not_count_against_itself(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $customer = $this->createCustomerModel();
        $basket = $this->basketWithCoupon($customer, $coupon);
        $first = $this->checkout($basket);
        $this->assertInvoiceHasDiscount($first, $coupon);

        $this->assertTrue($coupon->fresh()->isValid(Basket::find($basket->id), false), 'the basket own invoice must not use up the coupon');
        $again = $this->checkout(Basket::find($basket->id));

        $this->assertSame($first->id, $again->id);
        $this->assertInvoiceHasDiscount($again, $coupon);
        $this->assertSame($coupon->id, Basket::find($basket->id)->coupon_id);
    }

    public function test_the_per_customer_cap_counts_the_customer_own_pending_invoices_only(): void
    {
        $coupon = $this->limitedCoupon(['max_uses_per_customer' => 1]);
        $customer = $this->createCustomerModel();
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);

        $this->assertInvoiceHasDiscount($this->checkout($this->basketWithCoupon($customer, $coupon)), $coupon);

        Basket::query()->delete();
        $invoice = $this->checkout($this->basketWithCoupon($customer, $coupon));

        $this->assertInvoiceHasNoDiscount($invoice);
    }

    public function test_the_global_cap_reads_the_coupon_counter(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 2, 'usages' => 2]);

        $invoice = $this->checkout($this->basketWithCoupon($this->createCustomerModel(), $coupon));

        $this->assertInvoiceHasNoDiscount($invoice);
    }

    public function test_apply_coupon_is_refused_when_the_last_use_is_reserved(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);
        $basket = $this->basketWithCoupon($this->createCustomerModel(), null);

        $this->assertFalse($basket->applyCoupon($coupon->code));
        $this->assertSame(__('coupon.coupon_max_uses'), Session::get('error'));
        $this->assertNull($basket->fresh()->coupon_id);
    }

    public function test_the_invoice_total_matches_its_lines_when_the_discount_is_dropped(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);

        $invoice = $this->checkout($this->basketWithCoupon($this->createCustomerModel(), $coupon));

        $lines = $invoice->items()->get()->sum(fn (InvoiceItem $item) => $item->price() - $item->discountTotal());
        $this->assertEqualsWithDelta($lines, (float) $invoice->subtotal, 0.01, 'the invoice subtotal must equal the sum of its lines');
        $this->assertEqualsWithDelta((float) $invoice->subtotal + (float) $invoice->tax, (float) $invoice->total, 0.01);
    }

    public function test_checkout_does_not_pay_when_the_discount_was_dropped(): void
    {
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        Setting::updateSettings(['checkout.toslink' => 'https://example.com/tos']);
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);
        $customer = $this->createCustomerModel();
        $customer->markEmailAsVerified();
        $customer->update(['balance' => 100]);
        $this->basketWithCoupon($customer, $coupon);

        $basket = Basket::where('user_id', $customer->id)->firstOrFail();

        $response = $this->actingAs($customer)->post(route('front.store.basket.checkout'), $this->checkoutPayload($customer));

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertNull($basket->fresh()->coupon_id);
        $response->assertRedirect(route('front.invoices.show', $invoice));
        $response->assertSessionHas('warning', __('coupon.discount_removed_at_checkout'));
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->status);
        $this->assertNull($invoice->external_id, 'no payment must have been started');
        $this->assertEqualsWithDelta(100.0, (float) $customer->fresh()->balance, 0.001);
    }

    public function test_a_paid_invoice_records_one_use_per_coupon(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 5]);
        $customer = $this->createCustomerModel();
        $invoice = $this->reserve($customer, $coupon, Invoice::STATUS_PAID);
        $this->discountedItem($invoice, $coupon);

        (new CouponUsageListener)->handle(new InvoiceCompleted($invoice->fresh()));

        $this->assertSame(1, (int) $coupon->fresh()->usages, 'two lines carrying the same coupon are one use');
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
    }

    public function test_a_paid_invoice_is_always_counted_and_flagged_past_the_cap(): void
    {
        Log::spy();
        $coupon = $this->limitedCoupon(['max_uses' => 1, 'usages' => 1]);
        $invoice = $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PAID);

        (new CouponUsageListener)->handle(new InvoiceCompleted($invoice->fresh()));

        $this->assertSame(2, (int) $coupon->fresh()->usages, 'a paid discount is a fact and must be counted');
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_paid_invoice_within_the_cap_is_not_flagged(): void
    {
        Log::spy();
        $coupon = $this->limitedCoupon(['max_uses' => 1, 'max_uses_per_customer' => 1]);
        $invoice = $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PAID);

        (new CouponUsageListener)->handle(new InvoiceCompleted($invoice->fresh()));

        $this->assertSame(1, (int) $coupon->fresh()->usages);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_a_payment_is_counted_even_while_another_pending_invoice_holds_the_coupon(): void
    {
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        $paid = $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);
        $other = $this->reserve($this->createCustomerModel(), $coupon, Invoice::STATUS_PENDING);

        $paid->update(['status' => Invoice::STATUS_PAID]);
        (new CouponUsageListener)->handle(new InvoiceCompleted($paid->fresh()));
        $other->update(['status' => Invoice::STATUS_CANCELLED]);

        $this->assertSame(1, (int) $coupon->fresh()->usages, 'the paid invoice must be counted');
        $this->assertInvoiceHasNoDiscount($this->checkout($this->basketWithCoupon($this->createCustomerModel(), $coupon)));
    }

    public function test_a_coupon_worth_nothing_still_goes_to_payment(): void
    {
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        Setting::updateSettings(['checkout.toslink' => 'https://example.com/tos']);
        $coupon = $this->limitedCoupon(['max_uses' => 1]);
        \App\Models\Store\Pricing::where('related_type', 'coupon')->where('related_id', $coupon->id)->update(['monthly' => 0]);
        \Cache::forget('coupon_'.$coupon->id);
        $customer = $this->createCustomerModel();
        $customer->markEmailAsVerified();
        $customer->update(['balance' => 100]);
        $basket = $this->basketWithCoupon($customer, $coupon);

        $response = $this->actingAs($customer)->post(route('front.store.basket.checkout'), $this->checkoutPayload($customer));

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($coupon->id, $basket->fresh()->coupon_id);
        $response->assertSessionMissing('warning');
        $this->assertNotSame(route('front.invoices.show', $invoice), $response->headers->get('Location'));
    }

    private function checkoutPayload(Customer $customer): array
    {
        return [
            'gateway' => 'balance',
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'address' => $customer->address,
            'address2' => $customer->address2,
            'city' => $customer->city,
            'zipcode' => $customer->zipcode,
            'phone' => $customer->phone,
            'region' => $customer->region,
            'accept_tos' => 'on',
            'country' => $customer->country,
        ];
    }

    private function limitedCoupon(array $attributes): Coupon
    {
        return $this->createCoupon(array_merge(['code' => 'LIMITED'.uniqid(), 'is_global' => true], $attributes));
    }

    private function basketWithCoupon(Customer $customer, ?Coupon $coupon): Basket
    {
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create([
            'product_id' => $this->createProductModel('active', 10)->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
        ]);
        $basket->update(['coupon_id' => $coupon?->id]);

        return $basket->fresh();
    }

    private function checkout(Basket $basket): Invoice
    {
        // Same order as the controller: totals are read before the invoice is created.
        $basket->total();

        return InvoiceService::createInvoiceFromBasket($basket, $this->gateway())->fresh();
    }

    private function gateway()
    {
        return \App\Models\Billing\Gateway::where('uuid', 'balance')->first() ?? $this->createGatewayModel();
    }

    private function reserve(Customer $customer, Coupon $coupon, string $status): Invoice
    {
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => $status]);
        $this->discountedItem($invoice, $coupon);

        return $invoice;
    }

    private function discountedItem(Invoice $invoice, Coupon $coupon): void
    {
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'discount' => ['id' => $coupon->id, 'code' => $coupon->code, 'type' => 'percent', 'value_price' => 10, 'value_setup' => 0, 'sub_price' => 1, 'sub_setup' => 0],
        ]);
    }

    private function assertInvoiceHasNoDiscount(Invoice $invoice): void
    {
        $this->assertTrue($invoice->items()->get()->every(fn (InvoiceItem $item) => $item->couponId() === null), 'no invoice line may carry the coupon');
        $this->assertEqualsWithDelta(10.0, (float) $invoice->subtotal, 0.01, 'the invoice must be billed at the undiscounted price');
    }

    private function assertInvoiceHasDiscount(Invoice $invoice, Coupon $coupon): void
    {
        $this->assertSame($coupon->id, (int) $invoice->items()->first()->couponId(), 'the invoice line must carry the coupon');
        $this->assertEqualsWithDelta(9.0, (float) $invoice->subtotal, 0.01, 'the invoice must be billed at the discounted price');
    }
}
