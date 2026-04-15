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

use App\Services\Store\RecurringService;
use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    use PricingRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $types = app('extension')->getProductTypes()->keys()->merge(['none'])->toArray();
        $billing = app(RecurringService::class)->getRecurringTypes();

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'type' => ['required', 'string', 'max:255', Rule::in($types)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'billing' => ['required', 'string', 'max:255', Rule::in($billing)],
            'currency' => ['required', 'string', 'max:255', Rule::in(array_keys(currencies()->toArray()))],
            'server_id' => ['nullable', 'integer', Rule::exists('servers', 'id')],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_renewals' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ], $this->pricingRules());
    }

    protected function prepareForValidation()
    {
        $pricing = $this->input('pricing', []);
        $billing = $this->input('billing');
        $convertedPricing = $this->prepareForPricing($pricing);

        $this->merge([
            'server_id' => $this->server_id == 'none' ? null : (int) $this->server_id,
            'product_id' => $this->product_id == 'none' ? null : (int) $this->product_id,
            'pricing' => $convertedPricing,
        ]);
        if ($billing === 'onetime') {
            $this->merge([
                'expires_at' => null,
            ]);
        }
    }
}
