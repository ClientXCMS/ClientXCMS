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
use App\Events\Core\Invoice\InvoiceCreated;
use App\Mail\Invoice\InvoicePaidEmail;
use App\Models\Billing\Invoice;
use Illuminate\Notifications\RoutesNotifications;

class SendInvoiceNotification
{
    use RoutesNotifications;

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
     * @param  InvoiceCreated|InvoiceCompleted  $event
     */
    public function handle($event): void
    {
        if ($event instanceof InvoiceCreated) {
            $this->sendInvoiceCreatedNotification($event->invoice);
        } elseif ($event instanceof InvoiceCompleted) {
            $this->sendInvoiceCompletedNotification($event->invoice);
        }
    }

    private function sendInvoiceCreatedNotification(Invoice $invoice): void
    {
        $invoice->notifyCustomer();
    }

    private function sendInvoiceCompletedNotification(Invoice $invoice): void
    {
        $invoice->notifyCustomer(InvoicePaidEmail::class);
    }
}
