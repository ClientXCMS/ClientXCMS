<?php

namespace App\Contracts\Billing;

use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\DTO\Billing\Electronic\PaymentReportPayload;
use App\DTO\Billing\Electronic\ProviderStatusResult;
use App\DTO\Billing\Electronic\ProviderSubmissionResult;
use App\DTO\Billing\Electronic\TransactionReportPayload;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\EReportingPeriod;

interface ElectronicExchangeProviderInterface
{
    public function key(): string;

    public function capabilities(): array;

    public function submitInvoice(ElectronicDocument $document, GeneratedElectronicInvoice $artifact): ProviderSubmissionResult;

    public function submitTransactionReport(EReportingPeriod $period, TransactionReportPayload $payload): ProviderSubmissionResult;

    public function submitPaymentReport(EReportingPeriod $period, PaymentReportPayload $payload): ProviderSubmissionResult;

    public function fetchStatus(ElectronicDocument $document): ProviderStatusResult;
}
