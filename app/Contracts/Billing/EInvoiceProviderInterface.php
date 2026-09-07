<?php

namespace App\Contracts\Billing;

use App\Models\Billing\ElectronicDocument;

interface EInvoiceProviderInterface
{
    public function submit(ElectronicDocument $document): array;

    public function status(ElectronicDocument $document): array;

    public function reportTransaction(ElectronicDocument $document): array;

    public function reportPayment(ElectronicDocument $document): array;
}
