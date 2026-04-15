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

namespace App\Services\Store;

class TaxesService
{
    const MODE_TAX_INCLUDED = 'tax_included';

    const MODE_TAX_EXCLUDED = 'tax_excluded';

    const VAT_PERCENT_METADATA_KEY = 'vat_percent';

    const VAT_DISABLED_METADATA_KEY = 'vat_disabled';

    const VAT_RATE_BY_COUNTRY = 'vat_rate_by_country';

    const VAT_RATE_FIXED = 'vat_rate_fixed';

    const PRICE_HT = 'tax_excluded';

    const PRICE_TTC = 'tax_included';

    public static function getTaxAmount(float $price, float $taxPercent, ?string $mode = null): float
    {
        if ($mode == null) {
            $mode = config('store_mode_tax', self::MODE_TAX_EXCLUDED);
        }
        if ($price != 0) {
            if ($mode === self::MODE_TAX_INCLUDED) {
                return round(($price * ($taxPercent / 100)), 2);
            }
        }

        return round($price * ($taxPercent / 100), 2);
    }

    public static function getAmount(float $price, float $taxPercent, ?string $mode = null): float
    {
        if ($mode == null) {
            $mode = setting('store_mode_tax', self::MODE_TAX_EXCLUDED);
        }
        if ($mode === self::MODE_TAX_INCLUDED) {
            return round($price - self::getTaxAmount($price, $taxPercent, $mode), 2);
        }

        return round($price, 2);
    }

    public static function getVatPrice(float $ht, ?string $iso = null): float
    {
        return $ht * (self::getVatPercent($iso) / 100);
    }

    public static function getPriceWithVat(float $ht, ?string $iso = null)
    {
        return $ht + self::getVatPrice($ht, $iso);
    }

    public static function getPriceWithoutVat(float $ttc, ?string $iso = null)
    {
        return $ttc / (1 + (self::getVatPercent($iso) / 100));
    }

    public static function getVatPercent(?string $iso = null)
    {
        $enabled = setting('store_vat_enabled', true);
        if (! $enabled) {
            return 0;
        }
        if ($iso == null) {
            if (env('STORE_FIXED_VAT_RATE') != null && env('STORE_FIXED_VAT_RATE') != 0) {
                return env('STORE_FIXED_VAT_RATE');
            }
            if (auth()->check()) {
                $iso = auth()->user()->country;
                if (auth()->user()->getMetadata(self::VAT_PERCENT_METADATA_KEY)) {
                    return floatval(auth()->user()->getMetadata(self::VAT_PERCENT_METADATA_KEY));
                }
                if (auth()->user()->getMetadata(self::VAT_DISABLED_METADATA_KEY)) {
                    return 0;
                }
            } else {
                $iso = env('STORE_VAT_COUNTRY', 'FR');
            }
        }

        return self::arrayVatPercents()[strtoupper($iso)] ?? 20;
    }

    public static function arrayVatPercents(): array
    {
        return [
            'AT' => 20,
            'BE' => 21,
            'BG' => 20,
            'HR' => 25,
            'CY' => 19,
            'CZ' => 21,
            'DK' => 25,
            'EE' => 20,
            'DE' => 19,
            'GR' => 24,
            'FI' => 24,
            'FR' => 20,
            'HU' => 27,
            'IE' => 23,
            'IT' => 22,
            'LV' => 21,
            'LT' => 21,
            'LU' => 17,
            'MT' => 18,
            'NL' => 21,
            'PL' => 23,
            'PT' => 23,
            'RO' => 19,
            'SK' => 20,
            'SI' => 22,
            'ES' => 21,
            'SE' => 25,
            'GB' => 20,
        ];
    }
}
