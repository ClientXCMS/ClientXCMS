<?php

namespace Tests\Feature\Billing;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InvoiceCompleteTest extends TestCase
{
    use RefreshDatabase;

    private int $completedEvents = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->completedEvents = 0;
        Event::listen(InvoiceCompleted::class, function () {
            $this->completedEvents++;
        });
    }

    private function invoiceWithServiceItem(string $status): Invoice
    {
        $customer = Customer::factory()->create();
        $product = $this->createProductModel();
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'status' => $status,
            'total' => 12,
            'subtotal' => 10,
            'tax' => 2,
            'setupfees' => 0,
            'notes' => '',
            'currency' => 'EUR',
            'due_date' => now()->addDays(7),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Service',
            'description' => 'Service',
            'quantity' => 1,
            'unit_price_ht' => 10,
            'unit_price_ttc' => 12,
            'unit_setup_ht' => 0,
            'unit_setup_ttc' => 0,
            'type' => 'service',
            'related_id' => $product->id,
            'data' => ['billing' => 'monthly', 'currency' => 'EUR'],
        ]);

        return $invoice->fresh();
    }

    public static function closedStatuses(): array
    {
        return [
            'refunded' => [Invoice::STATUS_REFUNDED],
            'cancelled' => [Invoice::STATUS_CANCELLED],
        ];
    }

    public static function payableStatuses(): array
    {
        return [
            'pending' => [Invoice::STATUS_PENDING],
            'failed' => [Invoice::STATUS_FAILED],
        ];
    }

    #[DataProvider('closedStatuses')]
    public function test_payment_notification_does_not_reopen_closed_invoice(string $status): void
    {
        Log::spy();
        $invoice = $this->invoiceWithServiceItem($status);
        Setting::updateSettings(['billing_mode' => 'proforma']);
        $sequencesBefore = DB::table('invoice_sequences')->get()->toArray();

        $invoice->complete();

        $this->assertSame($status, $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->paid_at);
        $this->assertEquals($sequencesBefore, DB::table('invoice_sequences')->get()->toArray(), 'A refused payment must not consume an invoice number');
        $this->assertSame(0, Service::count());
        $this->assertSame(0, $this->completedEvents);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => ($context['invoice_id'] ?? null) === $invoice->id)->once();
    }

    #[DataProvider('payableStatuses')]
    public function test_payment_notification_completes_payable_invoice(string $status): void
    {
        $invoice = $this->invoiceWithServiceItem($status);

        $invoice->complete();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(1, Service::count());
        $this->assertSame(1, $this->completedEvents);
    }

    public function test_notification_on_stale_copy_does_not_reopen_invoice_cancelled_meanwhile(): void
    {
        Log::spy();
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_PENDING);
        $staleCopy = Invoice::find($invoice->id);
        Invoice::whereKey($invoice->id)->update(['status' => Invoice::STATUS_CANCELLED]);

        $staleCopy->complete();

        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
        $this->assertSame(0, Service::count());
        $this->assertSame(0, $this->completedEvents);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => ($context['invoice_id'] ?? null) === $invoice->id)->once();
    }

    public function test_already_paid_invoice_is_left_untouched(): void
    {
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_PAID);

        Invoice::find($invoice->id)->complete();

        $this->assertSame(0, Service::count());
        $this->assertSame(0, $this->completedEvents);
    }

    #[DataProvider('closedStatuses')]
    public function test_explicit_override_completes_closed_invoice(string $status): void
    {
        $invoice = $this->invoiceWithServiceItem($status);

        $invoice->complete(false, allowClosed: true);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(1, $this->completedEvents);
    }

    private function adminUpdatePayload(Invoice $invoice): array
    {
        return [
            'status' => Invoice::STATUS_PAID,
            'notes' => 'Manual validation',
            'paymethod' => 'none',
            'fees' => 0,
            'tax' => 2,
            'currency' => 'EUR',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];
    }

    public function test_admin_can_mark_cancelled_invoice_as_paid(): void
    {
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_CANCELLED);

        $response = $this->performAdminAction('PUT', route('admin.invoices.update', $invoice), $this->adminUpdatePayload($invoice), ['admin.manage_invoices']);

        $this->assertNotEquals(403, $response->status());
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertSame(1, $this->completedEvents);
    }

    public function test_admin_without_permission_cannot_mark_invoice_as_paid(): void
    {
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_CANCELLED);

        $response = $this->performAdminAction('PUT', route('admin.invoices.update', $invoice), $this->adminUpdatePayload($invoice), ['admin.show_invoices']);

        $response->assertStatus(403);
        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
        $this->assertSame(0, $this->completedEvents);
    }

    public function test_admin_mass_action_completes_cancelled_invoice(): void
    {
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_CANCELLED);

        $response = $this->performAdminAction('POST', route('admin.invoices.mass_action'), ['action' => 'complete', 'ids' => (string) $invoice->id], ['admin.manage_invoices']);

        $this->assertNotEquals(403, $response->status());
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(1, $this->completedEvents);
    }

    public function test_admin_mass_action_without_permission_is_refused(): void
    {
        $invoice = $this->invoiceWithServiceItem(Invoice::STATUS_CANCELLED);

        $response = $this->performAdminAction('POST', route('admin.invoices.mass_action'), ['action' => 'complete', 'ids' => (string) $invoice->id], ['admin.show_invoices']);

        $response->assertStatus(403);
        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
    }
}
