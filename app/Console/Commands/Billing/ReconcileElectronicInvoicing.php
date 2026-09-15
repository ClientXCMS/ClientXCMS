<?php

namespace App\Console\Commands\Billing;

use App\Jobs\Billing\SubmitElectronicInvoice;
use App\Jobs\Billing\SubmitEReportingPeriod;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\ElectronicDocumentEvent;
use App\Models\Billing\EReportingPeriod;
use App\Services\Billing\ElectronicProviderRegistry;
use Illuminate\Console\Command;

class ReconcileElectronicInvoicing extends Command
{
    protected $signature = 'einvoicing:reconcile';

    protected $description = 'Réconcilie les statuts et relance les transmissions électroniques échouées.';

    public function handle(ElectronicProviderRegistry $registry): int
    {
        ElectronicDocument::with('documentable')->whereIn('status', [ElectronicDocument::STATUS_PENDING, ElectronicDocument::STATUS_SUBMITTED])->where('provider', '!=', 'manual')->chunkById(100, function ($documents) use ($registry) {
            foreach ($documents as $document) {
                try {
                    $result = $registry->get($document->provider)->fetchStatus($document);
                    $document->update(['status' => $result->status, 'delivered_at' => $result->status === ElectronicDocument::STATUS_DELIVERED ? now() : $document->delivered_at, 'raw_response' => $result->response]);
                    ElectronicDocumentEvent::firstOrCreate(['electronic_document_id' => $document->id, 'provider_event_id' => hash('sha256', $document->idempotency_key.'|reconcile|'.$result->status)], ['provider_status' => $result->status, 'internal_status' => $result->status, 'event_code' => 'reconciled', 'payload' => $result->response, 'occurred_at' => now(), 'received_at' => now()]);
                } catch (\Throwable $exception) {
                    $document->update(['last_error_code' => class_basename($exception), 'last_error_message' => $exception->getMessage()]);
                }
            }
        });
        ElectronicDocument::with('documentable')->where('status', ElectronicDocument::STATUS_FAILED)->where('provider', '!=', 'manual')->where('updated_at', '<=', now()->subMinutes(5))->each(fn ($document) => SubmitElectronicInvoice::dispatch($document->documentable));
        EReportingPeriod::where('status', 'failed')->where('due_at', '<=', now())->each(fn ($period) => SubmitEReportingPeriod::dispatch($period));

        return self::SUCCESS;
    }
}
