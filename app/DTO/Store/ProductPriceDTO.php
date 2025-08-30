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
namespace App\DTO\Store;

use App\Services\Store\RecurringService;
use App\Services\Store\TaxesService;
use DragonCode\Contracts\Support\Jsonable;

class ProductPriceDTO implements Jsonable
{

    public string $mode;

    public float $price_ht;
    public float $setup_ht;
    public ?float $firstpayment_ht;

    public float $base_price;
    public float $base_setup;
    public ?float $base_firstpayment;

    public string $currency;
    public string $recurring;
    public bool   $free;
    public float $price;
    public float $setup;

    public function __construct(
        float  $recurringprice,
        ?float $setup,
        string $currency,
        string $recurring,
        ?float $firstpayment = null,
        ?string $mode = null,
    ) {
        $this->mode = $mode ?? setting('store_mode_tax', TaxesService::MODE_TAX_EXCLUDED);

        $this->base_price        = $recurringprice;
        $this->base_setup        = $setup ?? 0.0;
        $this->base_firstpayment = $firstpayment;

        $this->price_ht        = $this->normalizeToHt($recurringprice);
        $this->setup_ht        = $setup       !== null ? $this->normalizeToHt($setup)       : 0.0;
        $this->firstpayment_ht = $firstpayment!== null ? $this->normalizeToHt($firstpayment): null;

        $this->currency  = $currency;
        $this->recurring = $recurring;
        $this->free      = $this->price_ht == 0.0;
        $this->price = $this->price_ht;
        $this->setup = $this->setup_ht;

    }

    public function isModeTTC(): bool { return $this->mode === TaxesService::MODE_TAX_INCLUDED; }
    public function isModeHT(): bool  { return ! $this->isModeTTC(); }

    public function isFree(): bool { return $this->free; }

    public function getSymbol(): string { return currency_symbol($this->currency); }

    private function vatRate(): float   { return tax_percent(); }

    private function normalizeToHt(float $amount): float
    {

        if ($this->isModeHT()) {
            return round($amount, 2);
        }

        $rate = $this->vatRate();
        $ht   = $amount / (1 + $rate / 100);
        return round($ht, 2);
    }

    private function format(float $number): float
    {
        return fmod($number, 1.0) === 0.0 ? (float)(int)$number : round($number, 2);
    }

    public function priceHT(): float  { return $this->price_ht; }
    public function setupHT(): float  { return $this->setup_ht; }
    public function firstPaymentHT(): float { return $this->firstpayment_ht ?? 0.0; }

    public function setup(): float  { return $this->setup; }

    public function priceTTC(): float {
        return $this->isModeHT()
            ? TaxesService::getPriceWithVat($this->price_ht)
            : $this->base_price;
    }
    public function setupTTC(): float {
        return $this->isModeHT()
            ? TaxesService::getPriceWithVat($this->setup_ht)
            : $this->base_setup;
    }
    public function firstPaymentTTC(): float {
        if ($this->firstpayment_ht !== null) {
            return $this->isModeHT()
                ? TaxesService::getPriceWithVat($this->firstpayment_ht)
                : $this->base_firstpayment;
        }
        return 0.0;
    }

    public function hasSetup(): bool { return $this->setup_ht > 0.0; }

    public function displayPrice(?float $baseHT = null): float
    {
        $displayTtc = setting('display_product_price', TaxesService::PRICE_TTC) === TaxesService::PRICE_TTC;

        if ($baseHT === null) {

            $baseHT = $this->price_ht;
        }

        $price = $displayTtc
            ? TaxesService::getPriceWithVat($baseHT)
            : $baseHT;

        return $this->format($price);
    }

    public function getPriceByDisplayMode(?float $baseHT = null): float
    {
        return $this->displayPrice($baseHT);
    }

    public function recurringPayment(): float
    {
        return $this->recurring === 'onetime' ? 0.0 : $this->format($this->price_ht);
    }

    public function onetimePayment(): float
    {
        return $this->recurring !== 'onetime' ? 0.0 : $this->format($this->price_ht);
    }

    public function firstPayment(): float
    {
        $ht = $this->firstpayment_ht ?? ($this->price_ht + $this->setup_ht);
        return $this->format($ht);
    }

    public function billableAmount(): float
    {
        $htTotal = $this->price_ht + $this->setup_ht + ($this->firstpayment_ht ?? 0.0);
        $ttcTotal = TaxesService::getPriceWithVat($htTotal);
        return $this->isModeHT() ? $htTotal : TaxesService::getPriceWithoutVat($ttcTotal);
    }

    public function taxAmount(): float
    {
        return TaxesService::getVatPrice($this->price_ht + $this->setup_ht + ($this->firstpayment_ht ?? 0.0));
    }

    public function tax(): float
    {
        return $this->taxAmount();
    }

    public function taxRate(): float { return $this->vatRate(); }

    public function recurring(): array
    {
        return app(RecurringService::class)->get($this->recurring);
    }

    public function pricingMessage(bool $setup = true): string
    {
        if ($this->isFree()) {
            return trans('store.product.freemessage');
        }

        $unit = $this->recurring()['unit'];
        $firstHT = $this->firstpayment_ht ?? ($this->price_ht + $this->setup_ht);
        $recurringHT = $this->price_ht;

        if ($this->hasSetup() && $setup || $this->firstpayment_ht !== null) {
            return trans('store.product.setupmessage', [
                'first'     => $this->getPriceByDisplayMode($firstHT),
                'recurring' => $this->getPriceByDisplayMode($recurringHT),
                'currency'  => $this->getSymbol(),
                'tax'       => $this->taxTitle(),
                'unit'      => $unit,
            ]);
        }

        return trans('store.product.nocharge', [
            'first'    => $this->getPriceByDisplayMode($recurringHT),
            'currency' => $this->getSymbol(),
            'unit'     => $unit,
            'tax'      => $this->taxTitle(),
        ]);
    }

    public function taxTitle(): string
    {
        return setting('display_product_price', TaxesService::PRICE_TTC) === TaxesService::PRICE_TTC
            ? __('store.ttc')
            : __('store.ht');
    }

    public function getDiscountOnRecurring(ProductPriceDTO $monthlyprice): float
    {
        $monthlyprice = $monthlyprice->price_ht * $this->recurring()['months'];
        if ($monthlyprice == 0) {
            return 0;
        }
        return round(($monthlyprice - $this->price_ht - $this->setup_ht) / $monthlyprice * 100, 2);
    }

    public function hasDiscountOnRecurring(ProductPriceDTO $monthlyprice): bool
    {
        $discount = $this->getDiscountOnRecurring($monthlyprice);

        return $discount > 1 && $discount < 100;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode([
            'currency'         => $this->currency,
            'mode'             => $this->mode,
            'tax_rate'         => $this->taxRate(),

            'price_ht'         => $this->price_ht,
            'setup_ht'         => $this->setup_ht,
            'first_payment_ht' => $this->firstpayment_ht,
            'subtotal'      => $this->price_ht + $this->setup_ht + ($this->firstpayment_ht ?? 0.0),
            'tax'              => $this->taxAmount(),
            'price_ttc'        => $this->priceTTC(),
            'setup_ttc'        => $this->setupTTC(),
            'first_payment_ttc'=> $this->firstPaymentTTC(),
            'recurring'        => $this->recurring,
            'setup'            => $this->setup(),
            'recurringPayment' => $this->recurringPayment(),
            'onetimePayment'   => $this->onetimePayment(),
            'free'             => $this->free,
        ], $options);
    }
}
