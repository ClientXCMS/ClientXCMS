<?php

namespace App\Jobs\Billing;

use App\Contracts\Billing\ElectronicInvoiceRendererInterface;
use App\Models\Billing\AccountingExport;
use App\Models\Billing\PaymentTransaction;
use App\Services\Billing\AccountingProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportAccountingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [300, 900, 3600];

    public function __construct(public PaymentTransaction $transaction, public string $providerKey)
    {
        $this->afterCommit();
    }

    public function handle(AccountingProviderRegistry $registry, ElectronicInvoiceRendererInterface $renderer): void
    {
        $provider = $registry->get($this->providerKey);
        if (! $provider->enabled() || ($provider->activatedAt() && $this->transaction->occurred_at->lt($provider->activatedAt()))) {
            return;
        }
        $key = hash('sha256', implode('|', [$provider->key(), 'payment', $this->transaction->uuid]));
        $export = AccountingExport::firstOrCreate(['idempotency_key' => $key], ['exportable_type' => $this->transaction::class, 'exportable_id' => $this->transaction->id, 'provider' => $provider->key(), 'event_type' => $this->transaction->type, 'status' => AccountingExport::STATUS_PENDING]);
        if (in_array($export->status, [AccountingExport::STATUS_COMPLETED, AccountingExport::STATUS_MANUAL_REVIEW], true)) {
            return;
        }
        try {
            $artifact = $renderer->render($this->transaction->invoice);
            $result = $provider->recordPayment($this->transaction, $artifact, $export);
            $export->update(['status' => $result->status, 'external_id' => $result->externalId, 'payload_sha256' => $artifact->sha256, 'response' => $result->response, 'submitted_at' => now(), 'completed_at' => $result->status === AccountingExport::STATUS_COMPLETED ? now() : null, 'last_error_code' => null, 'last_error_message' => null]);
        } catch (\Throwable $exception) {
            $export->update(['status' => AccountingExport::STATUS_FAILED, 'last_error_code' => class_basename($exception), 'last_error_message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
