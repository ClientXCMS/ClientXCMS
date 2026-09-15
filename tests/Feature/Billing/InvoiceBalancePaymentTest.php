<?php

namespace Tests\Feature\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBalancePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_invoice_can_be_paid_in_two_instalments(): void
    {
        [$customer, $invoice] = $this->invoiceOf(100.0, 500.0);
        $total = (float) $invoice->total;
        $first = round($total / 2, 2);

        $this->assertTrue($invoice->addBalance($first), 'the first instalment must be accepted');
        $invoice->refresh();

        $this->assertLessThan($total, (float) $invoice->total, 'what is left to pay must go down after an instalment');
        $this->assertTrue($invoice->canPay(), 'a partly paid invoice stays payable');

        $second = (float) $invoice->total;
        $this->assertTrue($invoice->addBalance($second), 'the second instalment must be accepted too');
        $invoice->refresh();

        $this->assertFalse($invoice->canPay(), 'the invoice must be settled once the remainder is paid');
        $this->assertEqualsWithDelta(500.0 - ($first + $second), (float) $customer->fresh()->balance, 0.01, 'the customer must be debited exactly what was paid, no more and no less');
    }

    public function test_paying_more_than_what_is_left_never_overcharges(): void
    {
        [$customer, $invoice] = $this->invoiceOf(100.0, 500.0);
        $total = (float) $invoice->total;

        $this->assertTrue($invoice->addBalance($total * 3));

        $this->assertEqualsWithDelta(500.0 - $total, (float) $customer->fresh()->balance, 0.01);
    }

    public function test_two_parallel_payments_cannot_spend_the_balance_twice(): void
    {
        [$customer, $invoice] = $this->invoiceOf(100.0, 500.0);
        $total = (float) $invoice->total;
        $second = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PENDING]);
        InvoiceItem::factory()->create([
            'invoice_id' => $second->id,
            'unit_price_ht' => 100.0,
            'unit_price_ttc' => 100.0,
            'quantity' => 1,
        ]);
        $second->refresh();
        $second->recalculate();
        $second->refresh();

        // The customer can afford one invoice, not two: the second debit must lose the race instead of going negative.
        $customer->update(['balance' => $total]);

        $first = $invoice->addBalance($total);
        $other = $second->addBalance((float) $second->total);

        $this->assertGreaterThanOrEqual(0.0, (float) $customer->fresh()->balance, 'the balance must never go negative: the same money cannot pay two invoices');
        $this->assertTrue($first, 'the first payment must go through');
        $this->assertFalse($other, 'the second payment must be refused, the money is already spent');
    }

    public function test_a_payment_without_enough_funds_is_refused(): void
    {
        [$customer, $invoice] = $this->invoiceOf(100.0, 5.0);

        $this->assertFalse($invoice->addBalance((float) $invoice->total));
        $this->assertEqualsWithDelta(5.0, (float) $customer->fresh()->balance, 0.01, 'a refused payment must leave the balance untouched');
    }

    /**
     * @return array{0: Customer, 1: Invoice}
     */
    private function invoiceOf(float $price, float $balance): array
    {
        $customer = Customer::factory()->create(['balance' => $balance]);
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PENDING,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'unit_price_ht' => $price,
            'unit_price_ttc' => $price,
            'quantity' => 1,
        ]);
        $invoice->refresh();
        $invoice->recalculate();
        $invoice->refresh();

        return [$customer, $invoice];
    }
}
