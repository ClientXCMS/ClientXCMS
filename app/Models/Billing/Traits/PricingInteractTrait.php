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

use App\DTO\Store\ProductPriceDTO;
use App\Models\Store\Pricing;
use App\Services\Store\CurrencyService;
use App\Services\Store\PricingService;
use Illuminate\Support\Collection;

trait PricingInteractTrait
{
    public static function onbootPricingInteractTrait()
    {
        static::deleting(function ($model) {
            $model->pricing()->delete();
            PricingService::forgot();
        });
    }

    public function getFirstPrice(): ProductPriceDTO
    {
        /** @var array|null $pricing */
        $pricing = PricingService::for($this->id, $this->pricing_key ?? 'product')->first();
        if ($pricing == null) {
            return new ProductPriceDTO(0, 0, currency(), 'monthly');
        }
        $pricing = new Pricing($pricing);
        $first = $pricing->getFirstRecurringType();
        if ($first === null) {
            return new ProductPriceDTO(0, 0, currency(), 'monthly');
        }

        return new ProductPriceDTO($pricing->$first, $pricing->{"setup_$first"}, $pricing->currency, 'monthly');
    }

    public function pricingAvailable(?string $currency = null): array
    {
        $recurrings = Pricing::getRecurringTypes();
        $available = [];

        if ($currency === null) {
            $pricing = $this->getAllPricing($this->id);
            if ($pricing->isEmpty()) {
                return [];
            }
        } else {
            $pricing = [$this->getAllPricingCurrency($this->id, $currency)];
        }
        if (current($pricing) == null) {
            return $this->pricingAvailable();
        }
        foreach ($pricing as $price) {
            foreach ($recurrings as $recurring) {
                if ($price[$recurring] !== null) {
                    if (! is_float($price[$recurring])) {
                        continue;
                    }
                    $available[] = new ProductPriceDTO($price[$recurring], $price['setup_'.$recurring], $price['currency'], $recurring);
                }
            }
        }

        return $available;
    }

    public function hasPricesForCurrency(?string $currency = null): bool
    {
        if ($currency == null) {
            $currency = app(CurrencyService::class)->retrieveCurrency();
        }

        return $this->getPriceByCurrency($currency) !== null;
    }

    public function hasBilling(string $billing): bool
    {
        $pricing = $this->getAllPricing($this->id);
        foreach ($pricing as $price) {
            if ($price[$billing] !== null) {
                return true;
            }
        }

        return false;
    }

    public function getPriceByCurrency(string $currency, ?string $recurring = null): ProductPriceDTO
    {
        $price = 0;
        $pricing = $this->getAllPricingCurrency($this->id, $currency);
        $setup = 0;
        if ($pricing == null) {
            $pricing = $this->getAllPricing($this->id)->first();
            if ($pricing != null) {
                $pricing = new Pricing($pricing);
                $currency = $pricing->currency;
                if ($recurring == null) {
                    $recurring = $pricing->getFirstRecurringType() ?? 'monthly';
                }
                $price = $pricing->getAttribute($recurring) ?? 0;
                $setup = $pricing->getAttribute('setup_'.$recurring) ?? 0;
                if ($price == 0) {
                    return $this->getPriceByCurrency($currency, $pricing->getFirstRecurringType() ?? 'monthly');
                }
            } else {
                $recurring = 'monthly';
            }
        } else {
            $pricing = new Pricing($pricing);
            if ($recurring == null) {
                $recurring = $pricing->getFirstRecurringType() ?? 'monthly';
            }
            $price = $pricing->getAttribute($recurring) ?? -1;
            $setup = $pricing->getAttribute('setup_'.$recurring) ?? 0;
            if ($price == -1) {
                $default = $pricing->getFirstRecurringType() ?? 'monthly';
                if ($default != $recurring) {
                    return $this->getPriceByCurrency($currency, $default);
                }
            }
        }
        return new ProductPriceDTO($price, $setup, $currency, $recurring);
    }

    public function getAllPricing(int $related_id): Collection
    {
        return PricingService::for($related_id, $this->pricing_key ?? 'product');
    }

    public function getPricing(): Pricing
    {
        $pricing = Pricing::where('related_id', $this->id)->where('related_type', $this->pricing_key ?? 'product')->first();
        if ($pricing == null) {
            return new Pricing(['related_id' => $this->id, 'related_type' => $this->pricing_key ?? 'product', 'currency' => currency()]);
        }

        return $pricing;
    }

    public function getAllPricingCurrency(int $related_id, string $currency)
    {
        return PricingService::forCurrency($related_id, $this->pricing_key ?? 'product', $currency);
    }
}
