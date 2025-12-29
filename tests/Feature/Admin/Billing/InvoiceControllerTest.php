<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Billing\CustomItem;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Services\Store\TaxesService;
use App\Abstracts\PaymentMethodSourceDTO;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceControllerTest extends \Tests\TestCase
{
    use RefreshDatabase;
    public function test_admin_invoice_index(): void
    {
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.invoices.index'));
        $response->assertStatus(200);
    }

    public function test_admin_invoice_view(): void
    {
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.invoices.show', ['invoice' => $invoice->id]));
        $response->assertStatus(200);
    }

    public function test_admin_can_add_product_line_to_draft_invoice(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $product = $this->createProductModel();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.draft', ['invoice' => $invoice->id]), [
            'name' => 'Admin product line',
            'description' => 'Line added from admin',
            'unit_price_ttc' => 120,
            'unit_setup_ttc' => 30,
            'quantity' => 2,
            'related' => 'product',
            'related_id' => $product->id,
            'billing' => 'monthly',
        ]);

        $response->assertStatus(302)->assertSessionHas('success');

        $invoice->refresh();
        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $expectedUnitHt = TaxesService::getPriceWithoutVat(120);
        $expectedSetupHt = TaxesService::getPriceWithoutVat(30);
        $expectedSubtotal = ($expectedUnitHt + $expectedSetupHt) * 2;
        $expectedTax = TaxesService::getTaxAmount($expectedSubtotal, tax_percent());

        $this->assertSame('service', $item->type);
        $this->assertSame($product->id, $item->related_id);
        $this->assertEqualsWithDelta($expectedSubtotal, $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta($expectedTax, $invoice->tax, 0.01);
        $this->assertEqualsWithDelta($expectedSubtotal + $expectedTax, $invoice->total, 0.01);
    }

    public function test_admin_can_add_custom_item_with_expected_totals(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.draft', ['invoice' => $invoice->id]), [
            'name' => 'Custom line',
            'description' => 'Manual custom item',
            'unit_price_ttc' => 50,
            'unit_setup_ttc' => 10,
            'quantity' => 1,
            'related' => 'custom_item',
            'related_id' => 0,
        ]);

        $response->assertStatus(302)->assertSessionHas('success');

        $invoice->refresh();
        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $expectedUnitHt = TaxesService::getPriceWithoutVat(50);
        $expectedSetupHt = TaxesService::getPriceWithoutVat(10);
        $expectedSubtotal = $expectedUnitHt + $expectedSetupHt;
        $expectedTax = TaxesService::getTaxAmount($expectedSubtotal, tax_percent());

        $this->assertSame(CustomItem::CUSTOM_ITEM, $item->type);
        $this->assertEqualsWithDelta($expectedSubtotal, $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta($expectedTax, $invoice->tax, 0.01);
        $this->assertEqualsWithDelta($expectedSubtotal + $expectedTax, $invoice->total, 0.01);
    }

    public function test_admin_can_delete_item_from_draft_invoice(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price_ttc' => 20,
            'unit_price_ht' => TaxesService::getPriceWithoutVat(20),
            'unit_setup_ttc' => 0,
            'unit_setup_ht' => 0,
        ]);
        $invoice->recalculate();

        $response = $this->actingAs($admin, 'admin')->delete(route('admin.invoices.deleteitem', [
            'invoice' => $invoice->id,
            'invoiceItem' => $invoiceItem->id,
        ]));

        $response->assertStatus(302)->assertSessionHas('success');

        $invoice->refresh();
        $this->assertCount(0, $invoice->items);
        $this->assertEqualsWithDelta(0, $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta(0, $invoice->tax, 0.01);
        $this->assertEqualsWithDelta(0, $invoice->total, 0.01);
    }

    public function test_admin_can_notify_invoice(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PENDING]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.invoices.notify', ['invoice' => $invoice->id]));
        $response->assertStatus(302)->assertSessionHas('success');
    }

    public function test_admin_can_validate_invoice(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.validate', ['invoice' => $invoice->id]));

        $response->assertStatus(302)->assertSessionHas('success');
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->refresh()->status);
    }

    public function test_admin_can_set_invoice_to_draft(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.edit', ['invoice' => $invoice->id]));

        $response->assertStatus(302)->assertSessionHas('success');
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->refresh()->status);
    }

    public function test_admin_can_cancel_invoice_item(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $invoiceItem = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.cancelitem', [
            'invoice' => $invoice->id,
            'invoiceItem' => $invoiceItem->id,
        ]));

        $response->assertStatus(302)->assertSessionHas('success');
    }

    public function test_admin_can_pay_invoice(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::STATUS_PENDING,
            'total' => 10,
        ]);

        $sourceId = 'test-source';
        $sourceIdInCache = 'payment_methods_' . $customer->id;
        $sourceDTO = new PaymentMethodSourceDTO($sourceId, 'Visa', '1234', '12', '2025', $customer->id, 'balance');

        \Illuminate\Support\Facades\Cache::forever($sourceIdInCache, collect([$sourceId => $sourceDTO]));

        // We need to ensure GatewayService::getAvailable() is not cached or includes our balance gateway
        \App\Services\Store\GatewayService::forgotAvailable();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.pay', ['invoice' => $invoice->id]), [
            'source' => $sourceId,
        ]);

        $response->assertStatus(302);
    }

    public function test_admin_can_deliver_item(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $invoiceItem = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.invoices.deliver', [
            'invoice' => $invoice->id,
            'invoiceItem' => $invoiceItem->id,
        ]));

        $response->assertStatus(302)->assertSessionHas('success');
    }

    public function test_admin_can_view_pdf(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\GatewaySeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'invoice_number' => 'CTX-2024-05-0001']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.invoices.pdf', ['invoice' => $invoice->id]));

        $response->assertStatus(200)->assertHeader('Content-Type', 'application/pdf');
    }
}
