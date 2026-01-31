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

class CouponUsageListener
{
    public function handle(InvoiceCompleted $event): void
    {
        $couponsUsed = [];
        /** @var Invoice $invoice */
        $invoice = $event->invoice;
        /** @var InvoiceItem $item */
        foreach ($invoice->items as $item) {
            $couponId = $item->couponId();
            if ($couponId && ! in_array($couponId, $couponsUsed)) {
                $couponsUsed[] = [$couponId, $this->getCouponAmount($invoice, $couponId)];
            }
        }
        if (empty($couponsUsed)) {
            return;
        }
        foreach ($couponsUsed as [$couponId, $amount]) {
            $coupon = Coupon::find($couponId);
            if ($coupon) {
                $coupon->increment('usages');
                CouponUsage::insert([
                    'coupon_id' => $coupon->id,
                    'customer_id' => $invoice->customer_id,
                    'used_at' => now(),
                    'amount' => $amount,
                ]);
            }
        }
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
