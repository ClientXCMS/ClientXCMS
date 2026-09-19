<?php

namespace App\Contracts\Billing;

use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentTransaction;
use Carbon\CarbonInterface;

interface PaymentLedgerRecorderInterface
{
    public function record(Invoice $invoice, string $type, string $amount, CarbonInterface $occurredAt, string $idempotencyKey, ?string $externalId = null, ?PaymentTransaction $reverses = null, array $metadata = []): PaymentTransaction;
}
