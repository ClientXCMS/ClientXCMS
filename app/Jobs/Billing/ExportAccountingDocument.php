<?php

namespace App\Jobs\Billing;

use App\Contracts\Billing\ElectronicInvoiceRendererInterface;
use App\Models\Billing\AccountingExport;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use App\Services\Billing\AccountingProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportAccountingDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [300, 900, 3600];

    public function __construct(public Invoice|CreditNote $document, public string $providerKey)
    {
        $this->afterCommit();
    }

    public function handle(AccountingProviderRegistry $registry, ElectronicInvoiceRendererInterface $renderer): void
    {
        $provider = $registry->get($this->providerKey);
        if (! $provider->enabled() || ($provider->activatedAt() && $this->document->created_at->lt($provider->activatedAt()))) {
            return;
        }
        $invoice = $this->document instanceof CreditNote ? $this->document->invoice : $this->document;
        $legacyKey = $provider->key() === 'abby' ? 'abby_income_book_id' : ($provider->key() === 'pennylane' ? 'pennylane_invoice_id' : null);
        if ($legacyKey && $invoice->hasMetadata($legacyKey)) {
            return;
        }
        $event = $this->document instanceof CreditNote ? 'credit_note' : 'invoice';
        $fingerprint = hash('sha256', json_encode([$provider->key(), $event, $this->document::class, $this->document->getKey(), $this->document->identifier(), $invoice->billing_snapshot], JSON_THROW_ON_ERROR));
        $export = AccountingExport::firstOrCreate(['idempotency_key' => $fingerprint], ['exportable_type' => $this->document::class, 'exportable_id' => $this->document->getKey(), 'provider' => $provider->key(), 'event_type' => $event, 'status' => AccountingExport::STATUS_PENDING]);
        if (in_array($export->status, [AccountingExport::STATUS_COMPLETED, AccountingExport::STATUS_SUBMITTED, AccountingExport::STATUS_WAITING_PAYMENT], true)) {
            return;
        }
        if (in_array('defer_invoice_until_payment', $provider->capabilities(), true) && $this->document instanceof Invoice) {
            $export->update(['status' => AccountingExport::STATUS_WAITING_PAYMENT]);

            return;
        }
        try {
            $artifact = $renderer->render($this->document);
            $result = $provider->exportDocument($this->document, $artifact, $export);
            $export->update(['status' => $result->status, 'external_id' => $result->externalId, 'payload_sha256' => $artifact->sha256, 'response' => $result->response, 'submitted_at' => now(), 'completed_at' => $result->status === AccountingExport::STATUS_COMPLETED ? now() : null, 'last_error_code' => null, 'last_error_message' => null]);
        } catch (\Throwable $exception) {
            $export->update(['status' => AccountingExport::STATUS_FAILED, 'last_error_code' => class_basename($exception), 'last_error_message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
