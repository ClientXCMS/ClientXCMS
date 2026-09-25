<?php

namespace App\Contracts\Billing;

use App\Models\Billing\ElectronicDocument;

/** @deprecated Implement ElectronicExchangeProviderInterface and register it in ElectronicProviderRegistry. */
interface EInvoiceProviderInterface
{
    public function submit(ElectronicDocument $document): array;

    public function status(ElectronicDocument $document): array;

    public function reportTransaction(ElectronicDocument $document): array;

    public function reportPayment(ElectronicDocument $document): array;
}
