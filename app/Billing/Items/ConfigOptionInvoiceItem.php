<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\ConfigOption;

class ConfigOptionInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'config_option';
    }

    public function type(): string|array
    {
        return 'config_option';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return ConfigOption::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $configOption = $this->relatedType($item);
        if ($configOption == null) {
            throw new \Exception("Config option not found for invoice item {$item->id}");
        }
        if ($item->parent_id == null) {
            throw new \Exception("Parent id not found for invoice item {$item->id}");
        }
        if ($item->parent->delivered_at != null) {
            if (! $configOption->automatic) {
                return true;
            }
            $item->delivered_at = now();
            $item->save();
        }

        return true;
    }
}
