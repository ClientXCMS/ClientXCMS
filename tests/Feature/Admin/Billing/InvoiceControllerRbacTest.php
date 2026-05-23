<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function invoiceWithItem(): array
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        return [$invoice, $item];
    }

    public function test_deliver_blocks_admin_without_permission(): void
    {
        [$invoice, $item] = $this->invoiceWithItem();
        $response = $this->performAdminAction(
            'POST',
            route('admin.invoices.deliver', [$invoice, $item]),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_deliver_allows_admin_with_manage_invoices_permission(): void
    {
        [$invoice, $item] = $this->invoiceWithItem();
        $response = $this->performAdminAction(
            'POST',
            route('admin.invoices.deliver', [$invoice, $item]),
            [],
            ['admin.manage_invoices']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_INVOICES must not be blocked');
    }
}
