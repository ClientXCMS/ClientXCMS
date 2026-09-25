<?php

namespace Tests\Unit\Services\Billing;

use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\Invoice;
use App\Services\Billing\LocalElectronicExchangeProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalElectronicExchangeProviderTest extends TestCase
{
    public function test_it_writes_stable_private_artifacts_without_duplicates(): void
    {
        Storage::fake('local');
        $invoice = new Invoice(['invoice_number' => 'CTX/2026/001']);
        $invoice->created_at = Carbon::parse('2026-09-01');
        $document = new ElectronicDocument;
        $document->setRelation('documentable', $invoice);
        $artifact = new GeneratedElectronicInvoice('EN16931', '<xml/>', '%PDF-test', hash('sha256', '%PDF-test'), ['number' => 'CTX/2026/001']);

        $provider = new LocalElectronicExchangeProvider;
        $first = $provider->submitInvoice($document, $artifact);
        $second = $provider->submitInvoice($document, $artifact);

        $this->assertSame($first->artifactPath, $second->artifactPath);
        Storage::disk('local')->assertExists($first->artifactPath);
        $base = substr($first->artifactPath, 0, -4);
        Storage::disk('local')->assertExists($base.'/factur-x.xml');
        Storage::disk('local')->assertExists($base.'/manifest.json');
        $this->assertCount(3, Storage::disk('local')->allFiles('einvoicing/local/invoices'));
    }
}
