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

namespace App\Http\Requests\Provisioning;

use App\Rules\PricingValidation;
use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigOptionOptionRequest extends FormRequest
{
    use PricingRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'options' => 'required|array',
            'options.*.friendly_name' => 'required|string',
            'options.*.value' => 'required|string',
            'options.*.hidden' => 'nullable|boolean',
            'options.*.pricing' => [new PricingValidation],
            'options.*.pricing.*.price' => 'nullable|numeric|min:0',
            'options.*.pricing.*.setup' => 'nullable|numeric|max:255',
        ];
    }

    protected function prepareForValidation()
    {
        $options = $this->input('options', []);
        foreach ($options as $key => $option) {
            $pricing = $option['pricing'] ?? [];
            $convertedPricing = $this->prepareForPricing($pricing);
            $options[$key]['pricing'] = $convertedPricing;
        }
        $this->merge([
            'options' => $options,
        ]);
    }
}
