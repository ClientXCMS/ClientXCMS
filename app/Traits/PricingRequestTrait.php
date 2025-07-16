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
namespace App\Traits;

use App\Rules\PricingValidation;

trait PricingRequestTrait
{
    private function pricingRules(bool $required = true): array
    {
        if (str_contains($this->route()->uri, 'api')) {
            return [];
        }
        $rules = [];
        $pricing = $this->get('pricing');
        if ($required) {
            $rules['pricing'] = [new PricingValidation];
        }
        if ($pricing) {
            foreach ($pricing as $key => $value) {
                $rules['pricing.'.$key.'.price'] = 'nullable|numeric|min:0';
                $rules['pricing.'.$key.'.setupfee'] = 'nullable|numeric|max:255';
            }
        }

        return $rules;
    }

    private function prepareForPricing(array $pricing)
    {
        $convertedPricing = [];
        foreach ($pricing as $key => $value) {
            $convertedPricing[$key]['price'] = isset($value['price']) ? str_replace(',', '.', $value['price']) : null;
            $convertedPricing[$key]['setup'] = isset($value['setup']) ? str_replace(',', '.', $value['setup']) : null;
        }

        return $convertedPricing;
    }
}
