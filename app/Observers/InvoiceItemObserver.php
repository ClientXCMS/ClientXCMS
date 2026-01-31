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

namespace App\Observers;

use App\Models\Billing\CustomItem;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\InvoiceLog;
use App\Models\Provisioning\Service;

class InvoiceItemObserver
{
    public function deleting(InvoiceItem $model)
    {
        InvoiceLog::log($model->invoice, InvoiceLog::REMOVE_LINE, ['name' => $model->name]);
    }

    public function created(InvoiceItem $model)
    {
        InvoiceLog::log($model->invoice, InvoiceLog::ADD_LINE, ['name' => $model->name]);
    }

    public function deleted(InvoiceItem $item)
    {
        if ($item->type == 'renewal') {
            $service = Service::find($item->related_id);
            $service->update(['invoice_id' => null]);
        }
        if ($item->type == CustomItem::CUSTOM_ITEM) {
            CustomItem::find($item->related_id)->delete();
        }
    }

    public function updating(InvoiceItem $item)
    {
        if ($item->type == CustomItem::CUSTOM_ITEM) {
            $customItem = CustomItem::find($item->related_id);
            if ($customItem == null) {
                return;
            }
            $customItem->update($item->only('name', 'description', 'unit_price_ht', 'unit_setup_ht'));
        }
    }
}
