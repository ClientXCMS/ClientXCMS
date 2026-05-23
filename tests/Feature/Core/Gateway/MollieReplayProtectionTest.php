<?php

namespace Tests\Feature\Core\Gateway;

use Tests\TestCase;

class MollieReplayProtectionTest extends TestCase
{
    public function test_invoice_complete_is_idempotent(): void
    {
        $reflection = new \ReflectionMethod(\App\Models\Billing\Traits\InvoiceStateTrait::class, 'complete');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'STATUS_PAID',
            $body,
            'complete() must short-circuit when status already paid - this is what prevents Mollie webhook replay attacks (Mollie has no signature header, so the security model relies on idempotence)'
        );
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$this->status\s*===?\s*self::STATUS_PAID\s*\)\s*\{\s*return\s*;/',
            $body,
            'complete() must early-return on already-paid invoice'
        );
    }
}
