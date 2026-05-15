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
            if (! $coupon) {
                continue;
            }
            // Atomic enforcement of max_uses: a single SQL UPDATE WHERE
            // (max_uses = 0 OR usages < max_uses) prevents the TOCTOU
            // window between Coupon::isValid() at apply-time and the
            // listener firing at complete-time. Two parallel completions
            // that both passed isValid only get one increment; the
            // second one sees affected_rows = 0 and skips the usage row
            // (the discount on that invoice is preserved but the operator
            // sees the warning in the log so they can audit / refund).
            $updated = Coupon::where('id', $coupon->id)
                ->where(function ($q) {
                    $q->where('max_uses', 0)
                        ->orWhereColumn('usages', '<', 'max_uses');
                })
                ->update(['usages' => \DB::raw('usages + 1')]);
            if ($updated === 0) {
                logger()->warning('Coupon::max_uses exceeded under race - usage not recorded', [
                    'coupon_id' => $coupon->id,
                    'invoice_id' => $invoice->id,
                ]);

                continue;
            }
            // Per-customer cap enforced inside a transaction with a row
            // lock on the coupon, so two parallel checkouts for the same
            // customer cannot both insert past the limit.
            \DB::transaction(function () use ($coupon, $invoice, $amount) {
                $locked = Coupon::where('id', $coupon->id)->lockForUpdate()->first();
                if ($locked->max_uses_per_customer > 0) {
                    $existing = CouponUsage::where('coupon_id', $locked->id)
                        ->where('customer_id', $invoice->customer_id)
                        ->count();
                    if ($existing >= $locked->max_uses_per_customer) {
                        logger()->warning('Coupon::max_uses_per_customer exceeded under race - usage row skipped', [
                            'coupon_id' => $locked->id,
                            'customer_id' => $invoice->customer_id,
                            'invoice_id' => $invoice->id,
                        ]);

                        return;
                    }
                }
                CouponUsage::insert([
                    'coupon_id' => $locked->id,
                    'customer_id' => $invoice->customer_id,
                    'used_at' => now(),
                    'amount' => $amount,
                ]);
            });
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
