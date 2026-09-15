<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentLedgerRecorderInterface;
use App\Events\Core\Invoice\PaymentTransactionRecorded;
use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class PaymentLedgerService implements PaymentLedgerRecorderInterface
{
    public function record(Invoice $invoice, string $type, string $amount, CarbonInterface $occurredAt, string $idempotencyKey, ?string $externalId = null, ?PaymentTransaction $reverses = null, array $metadata = []): PaymentTransaction
    {
        $key = hash('sha256', $idempotencyKey);
        $transaction = PaymentTransaction::firstOrCreate(['idempotency_key' => $key], [
            'uuid' => (string) Str::uuid(),
            'invoice_id' => $invoice->getKey(),
            'reverses_id' => $reverses?->getKey(),
            'type' => $type,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => strtoupper($invoice->currency),
            'payment_method' => $invoice->paymethod,
            'external_id' => $externalId,
            'occurred_at' => $occurredAt,
            'metadata' => $metadata,
        ]);

        if ($transaction->wasRecentlyCreated) {
            event(new PaymentTransactionRecorded($transaction));
        }

        return $transaction;
    }
}
