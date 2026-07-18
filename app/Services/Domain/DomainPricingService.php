<?php

namespace App\Services\Domain;

use App\DTO\Store\ProductPriceDTO;
use App\Models\Store\DomainTld;
use App\Models\Store\DomainTldPrice;
use Illuminate\Support\Collection;

class DomainPricingService
{
    public const ACTION_REGISTER = 'register';
    public const ACTION_RENEW = 'renew';
    public const ACTION_TRANSFER = 'transfer';

    public static function normalizeExtension(string $extension): string
    {
        return '.' . ltrim(strtolower(trim($extension)), '.');
    }

    public function findTld(string $extension): ?DomainTld
    {
        return DomainTld::where('extension', self::normalizeExtension($extension))->where('status', 'active')->first();
    }

    public function availableForTld(string $extension, ?string $currency = null, string $action = self::ACTION_REGISTER): array
    {
        $tld = $this->findTld($extension);
        if ($tld === null) {
            return [];
        }
        $query = $tld->prices()->where('action', $action);
        if ($currency !== null) {
            $query->where('currency', $currency);
        }

        return $query->orderBy('id')->get()->map(function (DomainTldPrice $price) {
            return new ProductPriceDTO($price->price, $price->setup, $price->currency, $price->billing);
        })->all();
    }

    public function priceFor(string $extension, string $currency, string $billing, string $action = self::ACTION_REGISTER): ?ProductPriceDTO
    {
        $tld = $this->findTld($extension);
        if ($tld === null) {
            return null;
        }

        $price = $tld->prices()
            ->where('currency', $currency)
            ->where('action', $action)
            ->where('billing', $billing)
            ->first();

        if ($price === null) {
            $price = $tld->prices()->where('action', $action)->where('billing', $billing)->first();
        }
        if ($price === null) {
            return null;
        }

        return new ProductPriceDTO($price->price, $price->setup, $price->currency, $price->billing);
    }

    public function billingsFor(string $extension, string $action = self::ACTION_REGISTER): Collection
    {
        $tld = $this->findTld($extension);
        if ($tld === null) {
            return collect();
        }

        return $tld->prices()->where('action', $action)->pluck('billing')->unique()->values();
    }
}
