<?php

namespace Tests\Unit\Billing;

use App\Models\Billing\Invoice;
use App\Services\InvoiceExporterService;
use Carbon\Carbon;
use Tests\TestCase;

class InvoiceExporterServiceTest extends TestCase
{
    public function test_client_profile_excludes_sensitive_columns_and_values(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-2026-001',
            'total' => 120,
            'tax' => 20,
            'currency' => 'EUR',
            'status' => Invoice::STATUS_PAID,
            'notes' => 'internal note',
            'external_id' => 'provider-secret-id',
            'payment_method_id' => 42,
        ]);
        $invoice->created_at = Carbon::parse('2026-01-10 12:00:00');
        $invoice->due_date = Carbon::parse('2026-01-20 12:00:00');
        $invoice->paid_at = Carbon::parse('2026-01-15 12:00:00');

        $headers = InvoiceExporterService::getHeaderRow(InvoiceExporterService::PROFILE_CLIENT);
        $row = InvoiceExporterService::getInvoiceRow($invoice, InvoiceExporterService::PROFILE_CLIENT);

        $this->assertSame(['Invoice Number', 'Invoice Date', 'Due Date', 'Total', 'Tax', 'Currency', 'Status', 'Paid At'], $headers);
        $this->assertNotContains('Notes', $headers);
        $this->assertNotContains('Payment Method ID', $headers);
        $this->assertNotContains('UUID', $headers);
        $this->assertNotContains('provider-secret-id', $row);
        $this->assertNotContains('internal note', $row);
        $this->assertNotContains(42, $row);
    }

    public function test_each_export_path_is_unique(): void
    {
        $method = new \ReflectionMethod(InvoiceExporterService::class, 'uniquePath');
        $method->setAccessible(true);

        $this->assertNotSame($method->invoke(null, 'csv'), $method->invoke(null, 'csv'));
    }
}
