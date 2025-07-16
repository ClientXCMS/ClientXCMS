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
namespace App\DTO\Store;

use App\Services\Store\RecurringService;
use App\Services\Store\TaxesService;
use DragonCode\Contracts\Support\Jsonable;

class ProductPriceDTO implements Jsonable
{
    public float $price;

    public bool $free;

    public float $setup;

    public string $currency;

    public string $recurring;

    public float $recurringprice;

    public ?float $firstpaymentprice = null;

    public ?float $recurringprice_saved = null;

    public float $firstpaymentprice_saved;

    public float $setup_saved;

    /**
     * ProductPriceDTO constructor.
     *
     * @param  float|null  $firstpaymentprice  - Permet de définir un prix de premier paiement différent du prix récurrent
     */
    public function __construct(float $recurringprice, ?float $setup, string $currency, string $recurring, ?float $firstpaymentprice = null, bool $saved = false)
    {
        $this->firstpaymentprice = $firstpaymentprice ? TaxesService::getAmount($firstpaymentprice, tax_percent()) : null;
        $this->recurringprice = TaxesService::getAmount($recurringprice, tax_percent());
        $this->price = TaxesService::getAmount($recurringprice, tax_percent());
        $this->free = $recurringprice == 0;
        $this->currency = $currency;
        $this->setup = $setup ? TaxesService::getAmount($setup, tax_percent()) : 0;
        $this->recurring = $recurring;
        $this->firstpaymentprice_saved = $firstpaymentprice ?? 0;
        $this->recurringprice_saved = $recurringprice;
        $this->setup_saved = $setup ?? 0;
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function getSymbol(): string
    {
        return currency_symbol($this->currency);
    }

    public function hasSetup(): bool
    {
        return $this->setup > 0;
    }

    public function firstPayment(): float
    {
        if ($this->firstpaymentprice != null) {
            return round($this->firstpaymentprice + $this->setup, 2);
        }

        return round($this->price + $this->setup, 2);
    }

    public function recurringPayment(): float
    {
        if ($this->recurring == 'onetime') {
            return 0;
        }
        return round($this->recurringprice, 2);
    }

    public function onetimePayment(): float
    {
        if ($this->recurring != 'onetime') {
            return 0;
        }
        return round($this->price, 2);
    }

    public function recurring(): array
    {
        return app(RecurringService::class)->get($this->recurring);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode([
            'price' => $this->price,
            'subtotal' => $this->price + $this->setup,
            'free' => $this->free,
            'setup' => $this->setup,
            'currency' => $this->currency,
            'recurring' => $this->recurring,
            'recurringPayment' => $this->recurringPayment(),
            'onetimePayment' => $this->onetimePayment(),
            'tax' => $this->tax(),
            'tax_price' => TaxesService::getVatPrice($this->price),
            'tax_setup' => TaxesService::getVatPrice($this->setup),
        ]);
    }

    public function billableAmount()
    {
        return $this->firstpaymentprice_saved + $this->recurringprice_saved + $this->setup_saved;
    }

    public function setup(): float
    {
        return $this->setup;
    }

    public function tax(?float $amount = null): float
    {
        if ($amount != null) {
            return TaxesService::getVatPrice($amount);
        }

        return TaxesService::getVatPrice($this->recurringprice_saved);
    }

    public function getDiscountOnRecurring(ProductPriceDTO $monthlyprice): float
    {
        $monthlyprice = $monthlyprice->price * $this->recurring()['months'];
        if ($monthlyprice == 0) {
            return 0;
        }
        return round(($monthlyprice - $this->price - $this->setup) / $monthlyprice * 100, 2);
    }

    public function hasDiscountOnRecurring(ProductPriceDTO $monthlyprice): bool
    {
        $discount = $this->getDiscountOnRecurring($monthlyprice);

        return $discount > 1 && $discount < 100;
    }

    public function pricingMessage(bool $setup = true): string
    {
        if ($this->isFree()) {
            return trans('store.product.freemessage');
        }
        if ($this->hasSetup() && $setup || $this->firstpaymentprice_saved > 0) {
            return trans('store.product.setupmessage', ['first' => $this->getPriceByDisplayMode($this->hasSetup() && $setup ? $this->setup() : $this->firstpaymentprice_saved), 'recurring' => $this->getPriceByDisplayMode($this->recurringprice_saved), 'currency' => $this->getSymbol(),  'tax' => $this->taxTitle(true), 'unit' => $this->recurring()['unit']]);
        }

        return trans('store.product.nocharge', ['first' => $this->getPriceByDisplayMode($this->recurringprice_saved), 'currency' => $this->getSymbol(), 'unit' => $this->recurring()['unit'], 'tax' => $this->taxTitle(true)]);
    }

    public function getPriceByDisplayMode(?float $payment = null)
    {
        if ($payment === null) {
            $payment = $this->recurringprice_saved;
        }

        if (setting('display_product_price', TaxesService::PRICE_TTC) == TaxesService::PRICE_TTC && setting('store_mode_tax', TaxesService::MODE_TAX_INCLUDED) == TaxesService::MODE_TAX_EXCLUDED) {
            $price = $payment + $this->tax($payment);
        } else {
            $price = $payment;
        }
        if (fmod($price, 1) === 0.0) {
            return (int) $price;
        }
        return round($price, 2);
    }

    public function taxTitle(bool $ht = true): string
    {
        if (setting('display_product_price', TaxesService::PRICE_TTC) == TaxesService::PRICE_TTC) {
            return __('store.ttc');
        }
        if ($ht) {
            return __('store.ht');
        }

        return '';
    }
}
