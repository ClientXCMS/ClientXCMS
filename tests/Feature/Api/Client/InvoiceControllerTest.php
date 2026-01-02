<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private function authenticatedCustomer(): array
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['*']);

        return [$customer, $token->plainTextToken];
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function createInvoice(Customer $customer, string $status = Invoice::STATUS_PENDING): Invoice
    {
        return Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => $status,
        ]);
    }

    public function test_customer_can_list_invoices(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createInvoice($customer);
        $this->createInvoice($customer, Invoice::STATUS_PAID);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'uuid', 'status', 'total', 'formatted_total', 'currency', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'filters',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_filter_invoices_by_status(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createInvoice($customer, Invoice::STATUS_PENDING);
        $this->createInvoice($customer, Invoice::STATUS_PAID);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices?filter=' . Invoice::STATUS_PENDING);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_see_draft_invoices(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createInvoice($customer, Invoice::STATUS_DRAFT);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_cannot_see_other_customer_invoices(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $this->createInvoice($otherCustomer);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_can_view_invoice_details(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $invoice = $this->createInvoice($customer);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices/' . $invoice->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'status',
                    'subtotal',
                    'tax',
                    'total',
                    'formatted_total',
                    'currency',
                    'items',
                    'created_at',
                ],
                'available_gateways',
            ])
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'total' => 1.2,
                ],
            ]);
    }

    public function test_customer_cannot_view_other_customer_invoice(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $invoice = $this->createInvoice($otherCustomer);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices/' . $invoice->id);

        $response->assertNotFound();
    }

    public function test_customer_cannot_view_draft_invoice(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $invoice = $this->createInvoice($customer, Invoice::STATUS_DRAFT);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices/' . $invoice->id);

        $response->assertNotFound();
    }

    public function test_customer_can_download_invoice_pdf(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $invoice = $this->createInvoice($customer);

        $response = $this->withHeaders($this->authHeaders($token))
            ->get('/api/client/invoices/' . $invoice->id . '/download');

        $response->assertOk();
    }

    public function test_customer_cannot_download_other_customer_invoice(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $invoice = $this->createInvoice($otherCustomer);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/invoices/' . $invoice->id . '/download');

        $response->assertNotFound();
    }
}
