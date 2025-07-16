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

use App\Services\Store\TaxesService;

trait BasketRowCouponTrait
{
    private bool $enableCoupon = false;

    public function enableCoupon(bool $enable = true): void
    {
        $this->enableCoupon = $enable;
    }

    public function taxWithoutCoupon()
    {
        $this->enableCoupon(false);

        return TaxesService::getTaxAmount($this->subtotal(), $this->taxPercent());
    }

    public function subtotalWithoutCoupon()
    {
        $this->enableCoupon(false);

        return $this->recurringPaymentWithoutCoupon() + $this->setupWithoutCoupon() + $this->onetimePaymentWithoutCoupon();
    }

    public function recurringPaymentWithoutCoupon(bool $withQuantity = true, ?string $billing = null)
    {
        $this->enableCoupon(false);

        if ($this->billing == 'onetime') {
            return 0;
        }
        if (! $withQuantity) {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->recurringPayment();
        } else {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->recurringPayment() * $this->quantity;
        }

        return $this->applyCoupon($recurringPayment, self::PRICE) + $this->applyCoupon($this->optionRecurringPayment(), self::OPTION);
    }

    public function onetimePaymentWithoutCoupon(bool $withQuantity = true, ?string $billing = null)
    {
        $this->enableCoupon(false);

        if ($this->billing != 'onetime') {
            return 0;
        }
        if (! $withQuantity) {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->onetimePayment();
        } else {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->onetimePayment() * $this->quantity;
        }

        return $this->applyCoupon($onetimePayment, self::PRICE) + $this->applyCoupon($this->optionOnetimePayment(), self::OPTION);
    }

    public function setupWithoutCoupon(bool $withQuantity = true, ?string $billing = null)
    {
        $this->enableCoupon(false);
        if (! $withQuantity) {
            $setup = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->setup;
        } else {
            $setup = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->setup * $this->quantity;
        }

        return $this->applyCoupon($setup, self::SETUP_FEES) + $this->applyCoupon($this->optionSetup(), self::OPTION);
    }

    public function totalWithoutCoupon()
    {
        $this->enableCoupon(false);

        return $this->subtotalWithoutCoupon() + $this->taxWithoutCoupon();
    }
}
