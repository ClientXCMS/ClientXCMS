<?php

namespace Tests\Feature\Core\Gateway;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\Gateway\GatewayTestHelpers;
use Tests\TestCase;

class MollieNotificationTest extends TestCase
{
    use GatewayTestHelpers;
    use RefreshDatabase;

    private function notify(string $value, ?string $currency = null, array $attributes = []): Invoice
    {
        $this->createGateway('mollie');
        $invoice = $this->createInvoice(array_merge(['total' => 12, 'subtotal' => 12], $attributes));
        $this->fakeMolliePayment($invoice, $value, true, $currency);

        $this->post('/gateways/mollie/notification', ['id' => 'tr_proof'])->assertOk();

        return $invoice->fresh();
    }

    public function test_lower_amount_keeps_invoice_pending(): void
    {
        $this->assertSame(Invoice::STATUS_PENDING, $this->notify('1.20')->status);
    }

    public function test_higher_amount_keeps_invoice_pending(): void
    {
        $this->assertSame(Invoice::STATUS_PENDING, $this->notify('12.01')->status);
    }

    public function test_other_currency_keeps_invoice_pending(): void
    {
        $this->assertSame(Invoice::STATUS_PENDING, $this->notify('12.00', 'USD')->status);
    }

    public function test_malformed_amount_keeps_invoice_pending(): void
    {
        $this->assertSame(Invoice::STATUS_PENDING, $this->notify('12')->status);
    }

    public function test_matching_payment_pays_invoice(): void
    {
        $invoice = $this->notify('12.00', 'eur');

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame('tr_proof', $invoice->external_id);
    }

    public function test_decimal_total_matches_exact_value(): void
    {
        $this->assertSame(Invoice::STATUS_PAID, $this->notify('19.99', null, ['total' => 19.99, 'subtotal' => 19.99])->status);
    }

    public function test_open_payment_keeps_invoice_pending(): void
    {
        $this->createGateway('mollie');
        $invoice = $this->createInvoice(['total' => 12, 'subtotal' => 12]);
        $this->fakeMolliePayment($invoice, '12.00', false);

        $this->post('/gateways/mollie/notification', ['id' => 'tr_proof'])->assertOk();

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_replay_on_paid_invoice_does_not_complete_twice(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $invoice = $this->notify('12.00', null, ['status' => Invoice::STATUS_PAID]);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        Event::assertNotDispatched(InvoiceCompleted::class);
    }
}
