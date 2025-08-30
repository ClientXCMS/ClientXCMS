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

class PricingService
{
    public static function fetch()
    {
        if (! is_installed()) {
            return [];
        }
        if (app()->runningUnitTests()) {
            return \App\Models\Store\Pricing::get()->toArray();
        }

        return \Cache::remember('pricing', 60 * 60 * 24, function () {
            return \App\Models\Store\Pricing::get()->toArray();
        });
    }

    public static function forgot()
    {
        \Cache::forget('pricing');
    }

    public static function forProduct(int $product_id)
    {
        return collect(self::fetch())->where('related_id', $product_id)->where('related_type', 'product');
    }

    public static function forProductCurrency(int $product_id, string $currency)
    {
        return collect(self::forProduct($product_id))->where('currency', $currency)->first();
    }

    public static function for($related_id, $related_type)
    {
        return collect(self::fetch())->where('related_id', $related_id)->where('related_type', $related_type)->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });
    }

    public static function forCurrency($related_id, $related_type, $currency)
    {
        return collect(self::for($related_id, $related_type))->where('currency', $currency)->first();
    }
}
