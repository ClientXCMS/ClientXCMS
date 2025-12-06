<?php

namespace Tests\Feature\Client;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoices_index(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        InvoiceItem::factory(15)->create();
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.invoices.index'))->assertOk();
    }

    public function test_invoices_valid_filter(): void
    {
        $this->seed(StoreSeeder::class);

        Customer::factory(15)->create();
        InvoiceItem::factory(15)->create();

        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.invoices.index').'?filter=paid')->assertOk();
    }

    public function test_invoices_invalid_filter(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        InvoiceItem::factory(15)->create();
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.invoices.index').'?filter=suuuu')->assertRedirect();
    }

    public function test_invoices_can_show(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        /** @var Customer $user */
        $user = $invoice->customer;
        $response = $this->actingAs($user)->get(route('front.invoices.show', ['invoice' => $invoice]));
        $response->assertOk();
    }

    public function test_invoices_can_download(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        /** @var Customer $user */
        $user = $invoice->customer;
        $this->actingAs($user)->get(route('front.invoices.download', ['invoice' => $invoice]))->assertOk();
    }

    public function test_invoices_cannot_download(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        $user = Customer::where('id', '!=', $invoice->customer_id)->first();
        $this->actingAs($user)->get(route('front.invoices.download', ['invoice' => $invoice]))->assertNotFound();
    }

    public function test_invoices_can_pay(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        /** @var Customer $user */
        $user = $invoice->customer;
        $invoice->status = 'pending';
        $invoice->save();
        $this->actingAs($user)->get(route('front.invoices.pay', ['invoice' => $invoice, 'gateway' => 'balance']))->assertRedirect();
    }

    public function test_invoices_cannot_pay(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        /** @var Customer $user */
        $user = $invoice->customer;
        $invoice->status = 'completed';
        $invoice->save();
        $this->actingAs($user)->get(route('front.invoices.pay', ['invoice' => $invoice, 'gateway' => 'balance']))->assertRedirect(route('front.invoices.show', ['invoice' => $invoice->uuid]));
    }

    public function test_invoices_cannot_show(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        /** @var Customer $user */
        $user = Customer::where('id', '!=', $invoice->customer_id)->first();
        $this->actingAs($user)->get(route('front.invoices.show', ['invoice' => $invoice]))->assertNotFound();
    }

    public function test_invoices_add_balance(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        $customer = Customer::factory()->create(['balance' => 100]);
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        $invoice->customer_id = $customer->id;
        $invoice->save();
        $this->actingAs($customer)->post(route('front.invoices.balance', ['invoice' => $invoice]), [
            'amount' => 1,
        ])->assertRedirect(route('front.invoices.show', ['invoice' => $invoice->uuid]))->assertSessionHas('success', __('client.invoices.balance.success'));
        $customer->refresh();
        $invoice->refresh();
        $this->assertEquals(1, $invoice->balance);
        $this->assertEquals(99, $customer->balance);
    }

    public function test_invoices_add_balance_paid_invoice(): void
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        Customer::factory(15)->create();
        $customer = Customer::factory()->create(['balance' => 100]);
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        $invoice->customer_id = $customer->id;
        $invoice->save();
        $this->actingAs($customer)->post(route('front.invoices.balance', ['invoice' => $invoice]), [
            'amount' => 50,
        ])->assertRedirect(route('front.invoices.show', ['invoice' => $invoice->uuid]));
        $customer->refresh();
        $invoice->refresh();
        $this->assertEquals(98.8, $customer->balance);
        $this->assertEquals(0, $invoice->balance);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('balance', $invoice->paymethod);
    }

    public function test_invoices_add_balance_not_enough(): void
    {
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();

        $customer = Customer::factory()->create(['balance' => 100]);
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        $invoice->customer_id = $customer->id;
        $invoice->save();
        $this->actingAs($customer)->post(route('front.invoices.balance', ['invoice' => $invoice]), [
            'amount' => 150,
        ])->assertRedirect(route('front.invoices.show', ['invoice' => $invoice->uuid]))
            ->assertSessionHas('error', __('client.invoices.balance.balance_not_enough'));
    }
}
