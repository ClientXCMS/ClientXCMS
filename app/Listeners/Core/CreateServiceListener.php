<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace App\Listeners\Core;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Services\Billing\InvoiceService;

class CreateServiceListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @throws \Exception
     */
    public function handle(InvoiceCompleted $event): void
    {
        $invoice = $event->invoice;
        foreach ($invoice->items as $item) {
            if ($item->type == 'service') {
                InvoiceService::createServicesFromInvoiceItem($invoice, $item);
            }
        }
    }
}
