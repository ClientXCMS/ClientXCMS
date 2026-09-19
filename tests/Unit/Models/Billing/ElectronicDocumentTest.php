<?php

namespace Tests\Unit\Models\Billing;

use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\Invoice;
use PHPUnit\Framework\TestCase;

class ElectronicDocumentTest extends TestCase
{
    public function test_idempotency_key_is_stable_and_payload_sensitive(): void
    {
        $invoice = new Invoice(['uuid' => 'invoice-uuid', 'invoice_number' => 'CTX-2026-08-0001']);
        $invoice->id = 42;
        $first = ElectronicDocument::idempotencyKey($invoice, 'qonto', str_repeat('a', 64));
        $this->assertSame($first, ElectronicDocument::idempotencyKey($invoice, 'qonto', str_repeat('a', 64)));
        $this->assertNotSame($first, ElectronicDocument::idempotencyKey($invoice, 'qonto', str_repeat('b', 64)));
    }
}
