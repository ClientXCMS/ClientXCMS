<?php

namespace App\Services\Billing;

use App\Contracts\Billing\ElectronicExchangeProviderInterface;
use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\DTO\Billing\Electronic\PaymentReportPayload;
use App\DTO\Billing\Electronic\ProviderStatusResult;
use App\DTO\Billing\Electronic\ProviderSubmissionResult;
use App\DTO\Billing\Electronic\ReportPayload;
use App\DTO\Billing\Electronic\TransactionReportPayload;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\EReportingPeriod;
use Illuminate\Support\Facades\Storage;

class LocalElectronicExchangeProvider implements ElectronicExchangeProviderInterface
{
    public function key(): string
    {
        return 'local';
    }

    public function capabilities(): array
    {
        return ['einvoicing', 'transaction_reporting', 'payment_reporting', 'simulation'];
    }

    public function submitInvoice(ElectronicDocument $document, GeneratedElectronicInvoice $artifact): ProviderSubmissionResult
    {
        $source = $document->documentable;
        $date = ($source->created_at ?? now());
        $base = sprintf('einvoicing/local/invoices/%s/%s/%s/%s', $date->format('Y'), $date->format('m'), $this->safe($source->identifier()), $artifact->sha256);
        $this->putOnce($base.'.pdf', $artifact->pdf);
        $this->putOnce($base.'/factur-x.xml', $artifact->xml);
        $manifest = $artifact->manifest + ['provider' => 'local', 'simulation' => true, 'created_at' => now()->toIso8601String()];
        $this->putOnce($base.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return new ProviderSubmissionResult('local:'.$artifact->sha256, ElectronicDocument::STATUS_DELIVERED, $manifest, $base.'.pdf');
    }

    public function submitTransactionReport(EReportingPeriod $period, TransactionReportPayload $payload): ProviderSubmissionResult
    {
        return $this->submitReport($period, $payload);
    }

    public function submitPaymentReport(EReportingPeriod $period, PaymentReportPayload $payload): ProviderSubmissionResult
    {
        return $this->submitReport($period, $payload);
    }

    public function fetchStatus(ElectronicDocument $document): ProviderStatusResult
    {
        return new ProviderStatusResult($document->status, ['external_id' => $document->provider_document_id, 'simulation' => true]);
    }

    private function submitReport(EReportingPeriod $period, ReportPayload $payload): ProviderSubmissionResult
    {
        $base = sprintf('einvoicing/local/reports/%s/%s_%s/%s', $payload->type, $payload->periodStart, $payload->periodEnd, $payload->sha256);
        $xml = $this->reportXml($payload);
        $this->putOnce($base.'.xml', $xml);
        $manifest = ['provider' => 'local', 'simulation' => true, 'type' => $payload->type, 'period' => [$payload->periodStart, $payload->periodEnd], 'sha256' => $payload->sha256, 'totals' => $payload->totals];
        $this->putOnce($base.'.manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return new ProviderSubmissionResult('local:'.$payload->sha256, 'accepted', $manifest, $base.'.xml');
    }

    private function reportXml(ReportPayload $payload): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElement('ElectronicReporting'));
        foreach (['type' => $payload->type, 'periodStart' => $payload->periodStart, 'periodEnd' => $payload->periodEnd, 'currency' => $payload->currency] as $name => $value) {
            $root->appendChild($dom->createElement($name, htmlspecialchars($value)));
        }
        $entries = $root->appendChild($dom->createElement('Entries'));
        foreach ($payload->entries as $row) {
            $entry = $entries->appendChild($dom->createElement('Entry'));
            foreach ($row as $name => $value) {
                if (is_scalar($value) || $value === null) {
                    $entry->appendChild($dom->createElement((string) $name, htmlspecialchars((string) $value)));
                }
            }
        }

        return $dom->saveXML();
    }

    private function putOnce(string $path, string $contents): void
    {
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return;
        }
        $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(8));
        if (! $disk->put($temporaryPath, $contents) || ! $disk->move($temporaryPath, $path)) {
            $disk->delete($temporaryPath);
            throw new \RuntimeException("Impossible d’écrire atomiquement l’artefact local {$path}.");
        }
    }

    private function safe(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $value);
    }
}
