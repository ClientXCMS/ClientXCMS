<?php

namespace Tests\Fixtures\Gateway;

use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Mollie\Api\MollieApiClient;
use Stripe\ApiRequestor;

trait GatewayTestHelpers
{
    private array $overriddenEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->overriddenEnv as $key => $previous) {
            if ($previous === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $_SERVER[$key] = $previous;
                putenv("{$key}={$previous}");
            }
        }
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    protected function setEnvValue(string $key, string $value): void
    {
        if (! array_key_exists($key, $this->overriddenEnv)) {
            $this->overriddenEnv[$key] = $_ENV[$key] ?? null;
        }
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    protected function createGateway(string $uuid, string $status = 'active'): Gateway
    {
        return Gateway::create(['name' => $uuid, 'uuid' => $uuid, 'status' => $status, 'minimal_amount' => 0]);
    }

    protected function createInvoice(array $attributes = [], ?Customer $customer = null): Invoice
    {
        $customer ??= Customer::factory()->create();

        return Invoice::create(array_merge([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PENDING,
            'total' => 100,
            'subtotal' => 100,
            'tax' => 0,
            'setupfees' => 0,
            'notes' => '',
            'currency' => 'EUR',
            'due_date' => now()->addDays(7),
        ], $attributes));
    }

    protected function postStripeEvent(array $session, string $type = 'checkout.session.completed'): \Illuminate\Testing\TestResponse
    {
        $secret = 'whsec_proof_secret';
        $this->setEnvValue('STRIPE_WEBHOOK_SECRET', $secret);
        $this->setEnvValue('STRIPE_PRIVATE_KEY', 'sk_test_proof');
        $this->setEnvValue('STRIPE_PUBLIC_KEY', 'pk_test_proof');
        $payload = json_encode([
            'id' => 'evt_proof',
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => array_merge(['object' => 'checkout.session', 'id' => 'cs_test_proof'], $session)],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call('POST', '/gateways/stripe/notification', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    protected function fakeStripePaymentIntent(int $amountReceived): FakeStripeHttpClient
    {
        $client = new FakeStripeHttpClient([
            'id' => 'pi_test_proof',
            'object' => 'payment_intent',
            'amount' => $amountReceived,
            'amount_received' => $amountReceived,
            'application_fee_amount' => 0,
            'currency' => 'eur',
            'status' => 'succeeded',
        ]);
        ApiRequestor::setHttpClient($client);

        return $client;
    }

    protected function fakeMolliePayment(Invoice $invoice, string $value, bool $paid = true, ?string $currency = null): void
    {
        $this->setEnvValue('MOLLIE_KEY', 'test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM');
        $client = new MollieApiClient(new FakeMollieHttpAdapter([
            'resource' => 'payment',
            'id' => 'tr_proof',
            'mode' => 'test',
            'status' => $paid ? 'paid' : 'open',
            'paidAt' => $paid ? now()->toIso8601String() : null,
            'amount' => ['value' => $value, 'currency' => $currency ?? $invoice->currency],
            'metadata' => ['invoice_id' => $invoice->id],
        ]));
        $client->setApiKey('test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM');
        $this->app->instance(MollieApiClient::class, $client);
    }
}
