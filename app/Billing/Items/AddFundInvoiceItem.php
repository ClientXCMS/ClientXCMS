<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Addons\Fund\DTO\AddFundDTO;

class AddFundInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'add_fund';
    }

    public function type(): string|array
    {
        return class_exists(AddFundDTO::class) ? AddFundDTO::ADD_FUND_TYPE : 'add_fund';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        if (class_exists(AddFundDTO::class)) {
            return new AddFundDTO($item->invoice_id);
        }

        return null;
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $item->invoice->customer->addFund($item->unit_price_ht, 'Add funds from invoice #' . $item->invoice->id);
        $item->delivered_at = now();
        $item->save();

        return true;
    }
}
