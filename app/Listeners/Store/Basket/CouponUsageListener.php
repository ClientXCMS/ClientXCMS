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

namespace App\Listeners\Store\Basket;

use App\Events\Core\Invoice\InvoiceCompleted;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;
use Illuminate\Support\Facades\DB;

class CouponUsageListener
{
    public function handle(InvoiceCompleted $event): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->invoice;
        $couponIds = $invoice->items->map(fn (InvoiceItem $item) => $item->couponId())->filter()->unique();
        foreach ($couponIds as $couponId) {
            $this->recordUsage($invoice, (int) $couponId);
        }
    }

    private function recordUsage(Invoice $invoice, int $couponId): void
    {
        DB::transaction(function () use ($invoice, $couponId) {
            $coupon = Coupon::whereKey($couponId)->lockForUpdate()->first();
            if ($coupon === null) {
                return;
            }
            CouponUsage::insert([
                'coupon_id' => $coupon->id,
                'customer_id' => $invoice->customer_id,
                'used_at' => now(),
                'amount' => $this->getCouponAmount($invoice, $coupon->id),
            ]);
            Coupon::whereKey($coupon->id)->increment('usages');
            // A paid discount is a fact: it is always counted, and only a confirmed overflow is reported.
            $globalExceeded = $coupon->max_uses > 0 && (int) $coupon->getAttribute('usages') + 1 > $coupon->max_uses;
            $customerExceeded = $coupon->max_uses_per_customer > 0 && $coupon->usages()->where('customer_id', $invoice->customer_id)->count() > $coupon->max_uses_per_customer;
            if ($globalExceeded || $customerExceeded) {
                logger()->warning('Coupon cap exceeded by a paid invoice', [
                    'coupon_id' => $coupon->id,
                    'invoice_id' => $invoice->id,
                ]);
            }
        });
    }

    private function getCouponAmount(Invoice $invoice, int $couponId = 0): float
    {
        $amount = 0;
        foreach ($invoice->items as $item) {
            if ($item->couponId() == $couponId) {
                $amount += $item->discountTotal();
            }
        }

        return $amount;
    }
}
