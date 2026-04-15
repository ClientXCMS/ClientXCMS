<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Upgrade;

class UpgradeInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'upgrade';
    }

    public function type(): string|array
    {
        return 'upgrade';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return Upgrade::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $upgrade = $this->relatedType($item);
        if ($upgrade == null) {
            throw new \Exception("Upgrade not found for invoice item {$item->id}");
        }
        $result = $upgrade->deliver();
        if ($result->success) {
            $item->delivered_at = now();
            $item->save();
        } else {
            throw new \Exception("Upgrade {$upgrade->id} delivery failed Error : " . $result->message);
        }

        return true;
    }
}
