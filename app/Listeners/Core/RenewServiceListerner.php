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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Listeners\Core;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Models\Provisioning\ServiceRenewals;

class RenewServiceListerner
{
    public function handle(InvoiceCompleted $event): void
    {
        $invoice = $event->invoice;
        foreach ($invoice->items as $item) {
            if ($item->type == 'renewal' && $item->delivered_at == null) {
                $service = $item->relatedType();
                if ($service) {
                    $service->renew($item->data['billing'] ?? null);
                }
                $item->delivered_at = now();
                $item->save();
                ServiceRenewals::where('invoice_id', $invoice->id)->update(['renewed_at' => now()]);
            }
        }
    }
}
