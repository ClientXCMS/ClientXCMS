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

use App\Models\Store\Group;
use App\Traits\PricingRequestTrait;
use Illuminate\Validation\Rule;

class CouponRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use PricingRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'code' => ['required', 'string', 'max:255', Rule::unique('coupons')->ignore($this->coupon)],
            'type' => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'applied_month' => ['required', 'integer', 'min:-1', 'max:10000'],
            'free_setup' => ['required', 'boolean'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'first_order_only' => ['required', 'boolean'],
            'max_uses' => ['required', 'integer', 'min:0'],
            'max_uses_per_customer' => ['required', 'integer', 'min:0'],
            'usages' => ['required', 'integer', 'min:0'],
            'required_products' => ['nullable', 'array'],
            'required_products.*' => ['integer', 'exists:products,id'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0'],
            'is_global' => ['required', 'boolean'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ], $this->pricingRules());
    }

    protected function prepareForValidation()
    {
        $pricing = $this->input('pricing', []);
        $products = $this->input('products', []);
        $groups = $this->input('groups', []);
        $requiredProducts = $this->input('required_products', []);

        $convertedPricing = $this->prepareForPricing($pricing);

        $this->merge([
            'pricing' => $convertedPricing,
            'free_setup' => $this->free_setup == 'true' ? '1' : '0',
            'first_order_only' => $this->first_order_only == 'true' ? '1' : '0',
            'is_global' => $this->is_global == 'true' ? '1' : '0',
        ]);
        $tmp = $products;
        foreach ($groups as $group) {
            $tmp = array_merge($tmp, Group::find($group)->products->pluck('id')->toArray());
        }
        $this->merge([
            'products' => $tmp,
            'required_products' => $requiredProducts,
        ]);
    }
}
