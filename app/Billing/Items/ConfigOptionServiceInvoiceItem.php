<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\ConfigOptionService;

class ConfigOptionServiceInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'config_option_service';
    }

    public function type(): string|array
    {
        return 'config_option_service';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return ConfigOptionService::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $configOptionService = $this->relatedType($item);
        if ($configOptionService == null) {
            throw new \Exception("Config option service not found for invoice item {$item->id}");
        }
        if ($item->parent_id == null) {
            throw new \Exception("Parent id not found for invoice item {$item->id}");
        }
        if ($item->parent->delivered_at != null) {
            if (! $configOptionService->option->automatic) {
                return true;
            }
            $configOptionService->renew($item->data['expires_at']);
            $item->delivered_at = now();
            $item->save();
        }

        return true;
    }
}
