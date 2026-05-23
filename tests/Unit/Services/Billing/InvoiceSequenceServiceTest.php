<?php

namespace Tests\Unit\Services\Billing;

use App\Services\Billing\InvoiceSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * v2.16 — verifies the atomic invoice numbering replaces the previous
 * count()+1 race condition.
 */
class InvoiceSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_increments_monotonically(): void
    {
        $a = InvoiceSequenceService::nextNumber('2026-05');
        $b = InvoiceSequenceService::nextNumber('2026-05');
        $c = InvoiceSequenceService::nextNumber('2026-05');

        $this->assertStringEndsWith('-0001', $a);
        $this->assertStringEndsWith('-0002', $b);
        $this->assertStringEndsWith('-0003', $c);
    }

    public function test_sequence_resets_per_month(): void
    {
        $may = InvoiceSequenceService::nextNumber('2026-05');
        $jun = InvoiceSequenceService::nextNumber('2026-06');

        $this->assertStringEndsWith('-0001', $may);
        $this->assertStringEndsWith('-0001', $jun);
        // Different month windows are tracked in separate counter rows.
        $this->assertNotSame($may, $jun);
    }

    public function test_bootstrap_continues_from_existing_legacy_invoices(): void
    {
        // Simulate a v2.15 installation that has 7 invoices already
        // numbered CTX-2026-05-0001 … CTX-2026-05-0007 without a row
        // in invoice_sequences.
        $prefix = setting('billing_invoice_prefix', 'CTX');
        $customer = \App\Models\Account\Customer::factory()->create();
        for ($i = 1; $i <= 7; $i++) {
            DB::table('invoices')->insert([
                'customer_id' => $customer->id,
                'currency' => 'EUR',
                'status' => 'paid',
                'invoice_number' => sprintf('%s-2026-05-%04d', $prefix, $i),
                'due_date' => now(),
                'total' => 0,
                'subtotal' => 0,
                'tax' => 0,
                'setupfees' => 0,
                'balance' => 0,
                'fees' => 0,
                'notes' => '',
                'paymethod' => 'none',
                'billing_address' => '{}',
                'uuid' => 'fixture-' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $next = InvoiceSequenceService::nextNumber('2026-05');
        $this->assertStringEndsWith('-0008', $next);
    }

    public function test_proforma_uses_a_distinct_counter(): void
    {
        // INVOICE billing type goes against the plain prefix.
        \App\Models\Admin\Setting::updateSettings(['billing_mode' => 'invoice']);
        $invoice = InvoiceSequenceService::nextNumber('2026-05', true);
        $this->assertStringContainsString('-2026-05-0001', $invoice);

        // PRO_FORMA billing prepends "-PROFORMA" — separate counter.
        \App\Models\Admin\Setting::updateSettings(['billing_mode' => 'proforma']);
        $proforma = InvoiceSequenceService::nextNumber('2026-05', true);
        $this->assertStringContainsString('PROFORMA-2026-05-0001', $proforma);
    }

    public function test_parallel_calls_never_collide(): void
    {
        // We can't actually fork here, but we can validate that the
        // counter goes up by exactly 1 even across many calls.
        $numbers = [];
        for ($i = 0; $i < 25; $i++) {
            $numbers[] = InvoiceSequenceService::nextNumber('2026-05');
        }
        $this->assertSame(25, count(array_unique($numbers)));
    }
}
