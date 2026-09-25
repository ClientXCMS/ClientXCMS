<?php

namespace App\Contracts\Billing;

use App\DTO\Billing\Accounting\AccountingExportResult;
use App\DTO\Billing\Accounting\AccountingStatusResult;
use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\Models\Billing\AccountingExport;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentTransaction;

interface AccountingExportProviderInterface
{
    public function key(): string;

    public function capabilities(): array;

    public function enabled(): bool;

    public function activatedAt(): ?\DateTimeInterface;

    public function exportDocument(Invoice|CreditNote $document, GeneratedElectronicInvoice $artifact, AccountingExport $export): AccountingExportResult;

    public function recordPayment(PaymentTransaction $transaction, GeneratedElectronicInvoice $artifact, AccountingExport $export): AccountingExportResult;

    public function status(AccountingExport $export): AccountingStatusResult;
}
