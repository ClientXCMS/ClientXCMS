<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\CustomItem;
use App\Models\Billing\InvoiceItem;

class CustomInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'custom_item';
    }

    public function type(): string|array
    {
        return CustomItem::CUSTOM_ITEM;
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return CustomItem::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $item->delivered_at = now();
        $item->save();

        return true;
    }
}
