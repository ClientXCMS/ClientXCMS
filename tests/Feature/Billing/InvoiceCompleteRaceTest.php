<?php

namespace Tests\Feature\Billing;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceCompleteRaceTest extends TestCase
{
    use RefreshDatabase;

    private function pendingInvoice(): Invoice
    {
        $customer = Customer::factory()->create();

        return Invoice::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 100,
            'subtotal' => 100,
            'tax' => 0,
            'setupfees' => 0,
            'notes' => '',
            'currency' => 'EUR',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_complete_fires_event_only_once_under_race(): void
    {
        Event::fake([InvoiceCompleted::class]);
        $row = $this->pendingInvoice();

        // Two independent in-memory copies of the same invoice id, both
        // believing the row is still pending. This models two parallel
        // webhook handlers landing at the same instant.
        $a = Invoice::find($row->id);
        $b = Invoice::find($row->id);
        $a->complete();
        $b->complete();

        Event::assertDispatchedTimes(InvoiceCompleted::class, 1);
        $this->assertSame(Invoice::STATUS_PAID, $row->fresh()->status);
    }

    public function test_complete_keeps_existing_idempotent_short_circuit(): void
    {
        Event::fake([InvoiceCompleted::class]);
        $invoice = $this->pendingInvoice();
        $invoice->complete();
        $invoice->refresh();
        $invoice->complete();

        Event::assertDispatchedTimes(InvoiceCompleted::class, 1, 'second complete() on the same model instance must short-circuit before re-firing the event');
    }
}
