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


namespace App\Models\Billing\Traits;

use App\Events\Core\CheckoutCompletedEvent;
use App\Events\Core\Invoice\InvoiceCancelled;
use App\Events\Core\Invoice\InvoiceCompleted;
use App\Events\Core\Invoice\InvoiceFailed;
use App\Events\Core\Invoice\InvoiceRefunded;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Basket\Basket;
use App\Services\Billing\InvoiceService;

trait InvoiceStateTrait
{
    public function cancel(bool $clearBasket = true)
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }
        $this->status = self::STATUS_CANCELLED;
        $this->save();
        $this->items->map(function (InvoiceItem $item) {
            $item->cancel();
        });
        $this->clearServiceAssociation();
        $this->clearBasket($clearBasket);
        event(new InvoiceCancelled($this));
    }

    public function complete(bool $clearBasket = true)
    {
        if ($this->status === self::STATUS_PAID) {
            return;
        }

        if (InvoiceService::getBillingType() == InvoiceService::PRO_FORMA) {
            $date = $this->created_at->format('Y-m');
            $this->invoice_number = Invoice::generateInvoiceNumber($date, false);
        }
        $this->paid_at = now();
        $this->status = self::STATUS_PAID;
        $this->save();
        $this->items->map(function (InvoiceItem $item) {
            $item->uncancel();
        });

        $this->clearBasket($clearBasket);
        $this->generatePdf();
        event(new InvoiceCompleted($this));
    }

    public function refund(bool $clearBasket = true)
    {
        if ($this->status === self::STATUS_REFUNDED) {
            return;
        }
        $this->status = self::STATUS_REFUNDED;
        $this->save();

        $this->items->map(function (InvoiceItem $item) {
            $item->refund();
        });
        $this->clearBasket($clearBasket);
        event(new InvoiceRefunded($this));
    }

    public function fail(bool $clearBasket = true)
    {
        if ($this->status === self::STATUS_FAILED) {
            return;
        }
        $this->status = self::STATUS_FAILED;
        $this->save();

        $this->items->map(function (InvoiceItem $item) {
            $item->cancel();
        });
        $this->clearBasket($clearBasket);
        event(new InvoiceFailed($this));
    }

    private function clearBasket(bool $clearBasket = true)
    {
        if ($clearBasket) {
            if ($this->getMetadata('basket') !== null) {
                $basket = Basket::find($this->getMetadata('basket') ?? Basket::getBasket());
                if ($basket->completed_at !== null) {
                    return;
                }
                if ($this->status === self::STATUS_PAID) {
                    event(new CheckoutCompletedEvent($basket, $this));
                }
                $basket->clear(true);
            }
        }
    }
}
