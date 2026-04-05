<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;

class RenewalInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'service';
    }

    public function type(): string|array
    {
        return 'service';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return Service::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $service = $this->relatedType($item);
        if ($service == null) {
            throw new \Exception("Service not found for invoice item {$item->id}");
        }
        $service->renew($item->data['billing'] ?? null);
        $item->delivered_at = now();
        $item->save();
        \App\Models\Provisioning\ServiceRenewals::where('invoice_id', $item->invoice_id)->update(['renewed_at' => now()]);

        return true;
    }
}
