<?php

namespace App\Listeners\Core;

use App\Events\Core\Invoice\InvoiceCreated;
use App\Models\Billing\Invoice;
use App\Services\Billing\InvoiceService;

class IssueInvoice
{
    public function handle(InvoiceCreated $event): void
    {
        if ($event->invoice->status === Invoice::STATUS_DRAFT || InvoiceService::getBillingType() === InvoiceService::PRO_FORMA) {
            return;
        }

        $event->invoice->issue();
    }
}
