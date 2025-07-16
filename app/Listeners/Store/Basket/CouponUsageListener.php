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
 * Year: 2025
 */
namespace App\Listeners\Store\Basket;

use App\Events\Core\CheckoutCompletedEvent;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;

class CouponUsageListener
{
    public function handle(CheckoutCompletedEvent $event): void
    {
        $basket = $event->basket;
        /** @var Coupon $coupon */
        $coupon = $basket->coupon;
        if (! $coupon) {
            return;
        }
        if (! $coupon->isValid($basket)) {
            return;
        }
        if ($event->invoice->status !== 'paid') {
            return;
        }
        if ($basket->coupon) {
            $basket->coupon->increment('usages');
        }
        CouponUsage::insert([
            'coupon_id' => $coupon->id,
            'customer_id' => $event->invoice->customer_id,
            'used_at' => now(),
            'amount' => $basket->subtotal(),
        ]);
    }
}
