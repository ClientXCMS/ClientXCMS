<?php

namespace App\Jobs\Billing;

use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\ElectronicDocumentEvent;
use App\Models\Billing\EReportingPeriod;
use App\Services\Billing\ElectronicProviderRegistry;
use App\Services\Billing\ReportPayloadFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitEReportingPeriod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [300, 900, 3600];

    public function __construct(public EReportingPeriod $period)
    {
        $this->afterCommit();
    }

    public function handle(ElectronicProviderRegistry $registry, ReportPayloadFactory $factory): void
    {
        $period = $this->period->fresh();
        if (in_array($period->status, ['accepted', 'submitted'], true)) {
            return;
        }
        try {
            $payload = $factory->make($period);
            $provider = $registry->get($period->provider);
            $document = ElectronicDocument::firstOrCreate(['idempotency_key' => hash('sha256', $period->idempotency_key.'|'.$payload->sha256)], [
                'documentable_type' => $period::class, 'documentable_id' => $period->id, 'provider' => $provider->key(),
                'format' => 'canonical-'.$period->type.'-report', 'status' => ElectronicDocument::STATUS_PENDING, 'payload_sha256' => $payload->sha256,
            ]);
            if (in_array($document->status, [ElectronicDocument::STATUS_DELIVERED, 'accepted'], true)) {
                return;
            }
            $this->event($document, 'generated', ElectronicDocument::STATUS_PENDING, ['totals' => $payload->totals]);
            $document->update(['status' => ElectronicDocument::STATUS_SUBMITTED, 'submitted_at' => now()]);
            $this->event($document, 'submitted', ElectronicDocument::STATUS_SUBMITTED, ['provider' => $provider->key()]);
            $result = $period->type === EReportingPeriod::TYPE_PAYMENT ? $provider->submitPaymentReport($period, $payload) : $provider->submitTransactionReport($period, $payload);
            $period->update(['status' => $result->status, 'external_id' => $result->externalId, 'payload_sha256' => $payload->sha256, 'artifact_path' => $result->artifactPath, 'totals' => $payload->totals, 'submitted_at' => now(), 'last_error' => null]);
            $document->update(['provider_document_id' => $result->externalId, 'status' => $result->status, 'structured_document_path' => $result->artifactPath, 'delivered_at' => $result->status === 'accepted' ? now() : null, 'raw_response' => $result->response]);
            $this->event($document, $result->status, $result->status, $result->response);
        } catch (\Throwable $exception) {
            $period->update(['status' => 'failed', 'last_error' => $exception->getMessage()]);
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
