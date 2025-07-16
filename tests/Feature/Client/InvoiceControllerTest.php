<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
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
        $this->actingAs($user)->get(route('front.invoices.show', ['invoice' => $invoice]))->assertOk();
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
}
