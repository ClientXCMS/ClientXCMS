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


namespace App\Http\Requests\Store;

use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ConfigOptionRequest extends FormRequest
{
    use PricingRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge([
            'type' => 'required|in:text,checkbox,number,textarea,slider,radio,dropdown',
            'key' => 'required',
            'name' => 'required',
            'rules' => 'nullable',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'unit' => 'nullable|required_if:type,slider',
            'step' => 'nullable|required_if:type,slider|numeric|min:1',
            'default_value' => 'nullable',
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
            'required' => 'nullable',
            'sort_order' => 'nullable|numeric',
        ], $this->pricingRules(false));
    }

    protected function prepareForValidation()
    {
        $pricing = $this->input('pricing', []);

        $convertedPricing = $this->prepareForPricing($pricing);

        $this->merge([
            'pricing' => $convertedPricing,
        ]);
    }
}
