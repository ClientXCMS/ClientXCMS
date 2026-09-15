<?php

namespace App\Contracts\Billing;

use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;

interface ElectronicInvoiceRendererInterface
{
    public function render(Invoice|CreditNote $document): GeneratedElectronicInvoice;
}
