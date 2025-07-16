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
namespace App\Http\Requests\Provisioning;

use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigOptionServiceRequest extends FormRequest
{
    use PricingRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge([
            'value' => 'required',
            'config_option_id' => 'required|exists:config_options,id',
            'expires_at' => 'nullable|date',
        ], $this->pricingRules());
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
