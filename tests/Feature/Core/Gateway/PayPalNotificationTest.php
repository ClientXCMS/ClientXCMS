<?php

namespace Tests\Feature\Core\Gateway;

use App\Core\Gateway\PayPalMethodType;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PayPalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT_EMAIL = 'merchant@example.test';

    private array $envSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['PAYPAL_EMAIL', 'PAYPAL_SANDBOX'] as $key) {
            $this->envSnapshot[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        }
        $this->setEnv('PAYPAL_EMAIL', self::MERCHANT_EMAIL);
        $this->setEnv('PAYPAL_SANDBOX', 'sandbox');

        Gateway::firstOrCreate(['uuid' => PayPalMethodType::UUID], ['name' => 'PayPal', 'status' => 'active']);
        Http::fake(['*' => Http::response('VERIFIED', 200)]);
    }

    protected function tearDown(): void
    {
        foreach ($this->envSnapshot as $key => [$env, $server]) {
            $this->restoreEnv($_ENV, $key, $env);
            $this->restoreEnv($_SERVER, $key, $server);
        }

        parent::tearDown();
    }

    public function test_completed_ipn_pays_invoice(): void
    {
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING, 'external_id' => null]);

        $this->assertEmptyOk($this->postIpn($invoice, ['payment_status' => 'Completed', 'txn_id' => 'PAID-TXN', 'receiver_email' => 'Merchant@Example.TEST']));

        $fresh = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $fresh->status);
        $this->assertSame('PAID-TXN', $fresh->external_id);
    }

    public function test_reversal_of_own_txn_refunds(): void
    {
        $invoice = $this->createPayPalInvoice();

        $this->postIpn($invoice, ['payment_status' => 'Reversed', 'mc_gross' => '-12.00', 'parent_txn_id' => $invoice->external_id]);

        $fresh = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_REFUNDED, $fresh->status);
        $this->assertSame($invoice->external_id, $fresh->external_id);
    }

    public function test_canceled_reversal_behaviour_unchanged(): void
    {
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_REFUNDED]);

        $this->assertEmptyOk($this->postIpn($invoice, ['payment_status' => 'Canceled_Reversal', 'parent_txn_id' => $invoice->external_id]));

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_REFUNDED);
    }

    public function test_reversal_for_foreign_receiver_leaves_invoice_untouched(): void
    {
        $invoice = $this->createPayPalInvoice();

        $this->postIpn($invoice, [
            'payment_status' => 'Reversed',
            'mc_gross' => '-12.00',
            'parent_txn_id' => $invoice->external_id,
            'receiver_email' => 'someone-else@example.test',
        ]);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PAID);
    }

    public function test_completed_for_foreign_receiver_does_not_pay_invoice(): void
    {
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING]);

        $this->postIpn($invoice, ['payment_status' => 'Completed', 'receiver_email' => 'someone-else@example.test']);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PENDING);
    }

    public function test_ipn_is_ignored_when_merchant_email_is_not_configured(): void
    {
        $this->setEnv('PAYPAL_EMAIL', '');
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING]);

        $this->postIpn($invoice, ['payment_status' => 'Completed', 'receiver_email' => '']);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PENDING);
    }

    public function test_pending_does_not_overwrite_external_id(): void
    {
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING]);

        $this->postIpn($invoice, ['payment_status' => 'Pending']);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PENDING);
    }

    public function test_reversal_with_foreign_parent_txn_is_ignored(): void
    {
        $invoice = $this->createPayPalInvoice();

        $this->postIpn($invoice, ['payment_status' => 'Reversed', 'mc_gross' => '-12.00', 'parent_txn_id' => 'OTHER-TXN']);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PAID);
    }

    public function test_reversal_without_parent_txn_is_ignored(): void
    {
        $invoice = $this->createPayPalInvoice(['external_id' => null]);

        $this->postIpn($invoice, ['payment_status' => 'Reversed', 'mc_gross' => '-12.00']);

        $this->assertInvoiceUntouched($invoice, Invoice::STATUS_PAID);
    }

    public function test_ipn_for_invoice_of_another_gateway_is_ignored(): void
    {
        $pending = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING, 'paymethod' => 'stripe']);
        $paid = $this->createPayPalInvoice(['paymethod' => 'stripe']);

        $this->postIpn($pending, ['payment_status' => 'Completed']);
        $this->postIpn($paid, ['payment_status' => 'Reversed', 'mc_gross' => '-12.00', 'parent_txn_id' => $paid->external_id]);

        $this->assertInvoiceUntouched($pending, Invoice::STATUS_PENDING);
        $this->assertInvoiceUntouched($paid, Invoice::STATUS_PAID);
    }

    public function test_ipn_with_other_currency_is_ignored(): void
    {
        $pending = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING]);
        $paid = $this->createPayPalInvoice();

        $this->postIpn($pending, ['payment_status' => 'Completed', 'mc_currency' => 'USD']);
        $this->postIpn($paid, ['payment_status' => 'Reversed', 'mc_gross' => '-12.00', 'mc_currency' => 'USD', 'parent_txn_id' => $paid->external_id]);

        $this->assertInvoiceUntouched($pending, Invoice::STATUS_PENDING);
        $this->assertInvoiceUntouched($paid, Invoice::STATUS_PAID);
    }

    public function test_refusals_share_one_response(): void
    {
        $invoice = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING]);
        $foreign = $this->createPayPalInvoice(['status' => Invoice::STATUS_PENDING, 'paymethod' => 'stripe']);

        $responses = [
            $this->postIpn($invoice, ['payment_status' => 'Completed', 'receiver_email' => 'someone-else@example.test']),
            $this->postIpn($invoice, ['payment_status' => 'Completed', 'mc_currency' => 'USD']),
            $this->postIpn($invoice, ['payment_status' => 'Completed', 'mc_gross' => '1.00']),
            $this->postIpn($foreign, ['payment_status' => 'Completed']),
            $this->postIpn($invoice, ['payment_status' => 'Completed', 'custom' => '999999']),
        ];

        foreach ($responses as $response) {
            $this->assertEmptyOk($response);
        }
    }

    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function restoreEnv(array &$target, string $key, ?string $value): void
    {
        if ($value === null) {
            unset($target[$key]);

            return;
        }
        $target[$key] = $value;
    }

    private function createPayPalInvoice(array $attributes = []): Invoice
    {
        $customer = $this->createCustomerModel();

        return Invoice::factory()->create($attributes + [
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PAID,
            'paymethod' => PayPalMethodType::UUID,
            'currency' => 'EUR',
            'subtotal' => 10,
            'tax' => 2,
            'total' => 12,
            'external_id' => 'TXN-'.Str::random(12),
        ]);
    }

    private function postIpn(Invoice $invoice, array $overrides): TestResponse
    {
        return $this->post(route('gateways.notification', ['gateway' => PayPalMethodType::UUID]), $overrides + [
            'txn_id' => 'TXN-'.Str::random(12),
            'mc_gross' => '12.00',
            'mc_fee' => '0.50',
            'mc_currency' => 'EUR',
            'receiver_email' => self::MERCHANT_EMAIL,
            'custom' => (string) $invoice->id,
        ]);
    }

    private function assertEmptyOk(TestResponse $response): void
    {
        $this->assertSame(200, $response->status());
        $this->assertSame('', $response->getContent());
    }

    private function assertInvoiceUntouched(Invoice $invoice, string $status): void
    {
        $fresh = $invoice->fresh();
        $this->assertSame($status, $fresh->status);
        $this->assertSame($invoice->external_id, $fresh->external_id);
    }
}
