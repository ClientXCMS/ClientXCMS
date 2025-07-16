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
namespace App\Models\Store\Basket;

trait BasketCouponTrait
{
    public function subtotalWithoutCoupon()
    {
        return $this->rows->reduce(function ($total, $row) {
            return $total + $row->subtotalWithoutCoupon();
        }, 0);
    }

    public function taxWithoutCoupon()
    {
        return $this->rows->reduce(function ($total, $row) {
            return $total + $row->taxWithoutCoupon();
        }, 0);
    }

    public function totalWithoutCoupon()
    {
        return $this->rows->reduce(function ($total, $row) {
            return $total + $row->totalWithoutCoupon();
        }, 0);
    }

    public function recurringPaymentWithoutCoupon()
    {
        return $this->rows->reduce(function ($total, $row) {
            return $total + $row->recurringPaymentWithoutCoupon();
        }, 0);
    }

    public function setupWithoutCoupon()
    {
        return $this->rows->reduce(function ($total, BasketRow $row) {
            return $total + $row->setupWithoutCoupon();
        }, 0);
    }
}
