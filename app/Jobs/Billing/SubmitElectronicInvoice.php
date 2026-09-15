<?php

namespace App\Jobs\Billing;

use App\Contracts\Billing\ElectronicInvoiceRendererInterface;
use App\Models\Billing\CreditNote;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\ElectronicDocumentEvent;
use App\Models\Billing\Invoice;
use App\Services\Billing\ElectronicProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitElectronicInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [300, 900, 3600];

    public function __construct(public Invoice|CreditNote $source)
    {
        $this->afterCommit();
    }

    public function handle(ElectronicInvoiceRendererInterface $renderer, ElectronicProviderRegistry $registry): void
    {
        $provider = $registry->get();
        try {
            $artifact = $renderer->render($this->source);
            $key = ElectronicDocument::idempotencyKey($this->source, $provider->key(), $artifact->sha256);
            $document = ElectronicDocument::firstOrCreate(['idempotency_key' => $key], ['documentable_type' => $this->source::class, 'documentable_id' => $this->source->getKey(), 'provider' => $provider->key(), 'format' => 'factur-x-en16931', 'status' => ElectronicDocument::STATUS_PENDING, 'payload_sha256' => $artifact->sha256]);
            if ($document->status === ElectronicDocument::STATUS_DELIVERED) {
                return;
            }
            $this->event($document, 'generated', ElectronicDocument::STATUS_PENDING, ['manifest' => $artifact->manifest]);
            $document->update(['status' => ElectronicDocument::STATUS_SUBMITTED, 'submitted_at' => now()]);
            $this->event($document, 'submitted', ElectronicDocument::STATUS_SUBMITTED, ['provider' => $provider->key()]);
            $result = $provider->submitInvoice($document, $artifact);
            $document->update(['provider_document_id' => $result->externalId, 'status' => $result->status, 'structured_document_path' => $result->artifactPath, 'submitted_at' => now(), 'delivered_at' => $result->status === ElectronicDocument::STATUS_DELIVERED ? now() : null, 'raw_response' => $result->response, 'last_error_code' => null, 'last_error_message' => null]);
            $this->event($document, $result->status, $result->status, $result->response);
        } catch (\Throwable $exception) {
            if (isset($document)) {
                $document->update(['status' => ElectronicDocument::STATUS_FAILED, 'last_error_code' => class_basename($exception), 'last_error_message' => $exception->getMessage()]);
                $this->event($document, 'failed', ElectronicDocument::STATUS_FAILED, ['error' => $exception->getMessage()]);
            }
            throw $exception;
        }
    }

    private function event(ElectronicDocument $document, string $code, string $status, array $payload): void
    {
        ElectronicDocumentEvent::firstOrCreate(['electronic_document_id' => $document->id, 'provider_event_id' => hash('sha256', $document->idempotency_key.'|'.$code)], ['provider_status' => $status, 'internal_status' => $status, 'event_code' => $code, 'payload' => $payload, 'occurred_at' => now(), 'received_at' => now()]);
    }
}
