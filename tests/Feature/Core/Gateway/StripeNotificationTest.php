<?php

namespace Tests\Feature\Core\Gateway;

use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\Gateway\GatewayTestHelpers;
use Tests\TestCase;

class StripeNotificationTest extends TestCase
{
    use GatewayTestHelpers;
    use RefreshDatabase;

    private function checkoutSession(Invoice $invoice, array $overrides = []): array
    {
        return array_merge([
            'amount_subtotal' => 10000,
            'amount_total' => 10000,
            'currency' => 'eur',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_proof',
            'metadata' => ['invoice_id' => (string) $invoice->id],
        ], $overrides);
    }

    private function pendingInvoice(array $attributes = []): Invoice
    {
        $this->createGateway('stripe');
        $this->fakeStripePaymentIntent(10000);

        return $this->createInvoice(array_merge(['total' => 100, 'subtotal' => 100], $attributes));
    }

    public function test_lower_amount_keeps_invoice_pending(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice, ['amount_subtotal' => 100, 'amount_total' => 100]))->assertOk();

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_other_currency_keeps_invoice_pending(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice, ['currency' => 'usd']))->assertOk();

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_unpaid_session_keeps_invoice_pending(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice, ['payment_status' => 'unpaid']))->assertOk();

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_async_payment_failed_marks_invoice_as_failed(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice, ['payment_status' => 'unpaid']), 'checkout.session.async_payment_failed')->assertOk();

        $this->assertSame(Invoice::STATUS_FAILED, $invoice->fresh()->status);
    }

    #[DataProvider('closedStatuses')]
    public function test_async_payment_failed_does_not_reopen_closed_invoice(string $status): void
    {
        $invoice = $this->pendingInvoice(['status' => $status]);

        $this->postStripeEvent($this->checkoutSession($invoice, ['payment_status' => 'unpaid']), 'checkout.session.async_payment_failed')->assertOk();

        $this->assertSame($status, $invoice->fresh()->status);
    }

    public static function closedStatuses(): array
    {
        return ['cancelled' => [Invoice::STATUS_CANCELLED], 'refunded' => [Invoice::STATUS_REFUNDED]];
    }

    public function test_matching_session_pays_invoice(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice, ['currency' => 'EUR']))->assertOk();

        $fresh = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $fresh->status);
        $this->assertSame('pi_test_proof', $fresh->external_id);
    }

    public function test_async_payment_succeeded_pays_invoice(): void
    {
        $invoice = $this->pendingInvoice();

        $this->postStripeEvent($this->checkoutSession($invoice), 'checkout.session.async_payment_succeeded')->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_exclusive_tax_added_by_stripe_still_pays_invoice(): void
    {
        $invoice = $this->pendingInvoice(['total' => 120, 'subtotal' => 100, 'tax' => 20]);

        $this->postStripeEvent($this->checkoutSession($invoice, ['amount_subtotal' => 12000, 'amount_total' => 14400]))->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_decimal_total_is_rounded_not_truncated(): void
    {
        $invoice = $this->pendingInvoice(['total' => 19.99, 'subtotal' => 19.99]);

        $this->postStripeEvent($this->checkoutSession($invoice, ['amount_subtotal' => 1999, 'amount_total' => 1999]))->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_replay_on_paid_invoice_skips_side_effects(): void
    {
        $invoice = $this->pendingInvoice(['status' => Invoice::STATUS_PAID, 'external_id' => 'pi_original']);
        $client = $this->fakeStripePaymentIntent(10000);

        $this->postStripeEvent($this->checkoutSession($invoice))->assertOk();

        $fresh = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $fresh->status);
        $this->assertSame('pi_original', $fresh->external_id);
        $this->assertSame([], $client->requests);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $invoice = $this->pendingInvoice();
        $this->setEnvValue('STRIPE_WEBHOOK_SECRET', 'whsec_proof_secret');

        $this->call('POST', '/gateways/stripe/notification', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'checkout.session.completed']))->assertStatus(400);

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }
}
