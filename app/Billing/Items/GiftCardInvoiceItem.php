<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Addons\Giftcard\Models\Giftcard;
use App\Addons\Giftcard\Notifications\RedeemGiftcardMail;

class GiftCardInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'gift_card';
    }

    public function type(): string|array
    {
        return class_exists(Giftcard::class) ? Giftcard::GIFT_CARD_TYPE : 'gift_card';
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        if (class_exists(Giftcard::class)) {
            return Giftcard::find($item->related_id);
        }

        return null;
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $giftCard = $this->relatedType($item);
        if ($giftCard == null) {
            throw new \Exception("Gift card not found for invoice item {$item->id}");
        }

        $customer = $giftCard->customer;
        if ($customer == null) {
            $customer = $item->invoice->customer;
        }

        if ($customer == null) {
            throw new \Exception("Customer not found for invoice item {$item->id}");
        }

        if ($giftCard->isValid($customer)) {
            $customer->notify(new RedeemGiftcardMail($giftCard, $item->data['message'] ?? ''));
            $item->delivered_at = now();
            $item->save();

            return true;
        } else {
            throw new \Exception("Gift card {$giftCard->code} is not valid for invoice item {$item->id}");
        }
    }
}
