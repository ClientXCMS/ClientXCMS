<?php

namespace Tests\Feature\Api\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_invoice_index()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        Invoice::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->performAction('GET', '/api/application/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_api_invoice_store()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();

        $response = $this->performAction('POST', '/api/application/invoices', ['*'], [
            'customer_id' => $customer->id,
            'currency' => 'EUR',
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'currency' => 'EUR',
        ]);
    }

    public function test_api_invoice_show()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $response = $this->performAction('GET', '/api/application/invoices/'.$invoice->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $invoice->id);
    }

    public function test_api_invoice_update()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'notes' => 'Old note']);

        $response = $this->performAction('POST', '/api/application/invoices/'.$invoice->id, ['*'], [
            'notes' => 'New note',
            'status' => $invoice->status,
            'currency' => $invoice->currency,
            'customer_id' => $invoice->customer_id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => 'New note',
        ]);
    }

    public function test_api_invoice_destroy()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_DRAFT]);

        $response = $this->performAction('DELETE', '/api/application/invoices/'.$invoice->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_api_invoice_validate()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_DRAFT]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->performAction('POST', "/api/application/invoices/{$invoice->id}/validate");

        $response->assertStatus(200);
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->refresh()->status);
    }

    public function test_api_invoice_edit()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PENDING]);

        $response = $this->performAction('POST', "/api/application/invoices/{$invoice->id}/edit");

        $response->assertStatus(200);
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->refresh()->status);
    }

    public function test_api_invoice_notify()
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PENDING]);

        $response = $this->performAction('GET', "/api/application/invoices/{$invoice->id}/notify");

        $response->assertStatus(200);
    }
}
