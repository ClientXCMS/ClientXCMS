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

namespace App\Http\Requests\Store\Basket;

use App\Contracts\Store\ProductTypeInterface;
use App\Core\Domain\Nameserver;
use App\Models\Store\DomainTld;
use App\Services\Domain\DomainPricingService;
use App\Services\Store\CurrencyService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BasketConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->product?->type === ProductTypeInterface::DOMAIN && is_string($this->input('domain'))) {
            $domain = strtolower(trim($this->input('domain')));
            $tld = DomainTld::where('status', 'active')->orderByRaw('LENGTH(extension) DESC')->get()
                ->first(fn (DomainTld $candidate) => str_ends_with($domain, $candidate->extension)
                    && strlen($domain) > strlen($candidate->extension));
            $this->merge(['domain' => $domain, 'tld' => $tld?->extension]);
        }
        if ($this->has('nameservers')) {
            $this->merge(['nameservers' => Nameserver::normalizeAll($this->input('nameservers'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->product->type === ProductTypeInterface::DOMAIN && is_string($this->input('tld')) && $this->input('tld') !== '') {
            $currency = $this->input('currency');
            $authorizedBilling = is_string($currency)
                ? app(DomainPricingService::class)->billingsFor($this->input('tld'), $currency, $this->input('operation') === 'transfer' ? DomainPricingService::ACTION_TRANSFER : DomainPricingService::ACTION_REGISTER)->toArray()
                : [];
        } else {
            $authorizedBilling = collect($this->product->pricingAvailable())->map(function ($price) {
                return $price->recurring;
            })->unique()->toArray();
        }
        /** @var ProductTypeInterface $productType */
        $productType = $this->product->productType();
        $rules = [
            'billing' => ['required', 'string', Rule::in($authorizedBilling)],
            'currency' => ['required', 'string', Rule::in(app(CurrencyService::class)->getCurrenciesKeys())],
        ];
        if ($this->product->type === ProductTypeInterface::DOMAIN) {
            // A domain must use an active catalog entry and an exact tariff.
            $rules['tld'] = ['required', 'string', function ($attribute, $value, $fail) {
                if (app(DomainPricingService::class)->findTld($value) === null) {
                    $fail(__('validation.exists', ['attribute' => $attribute]));
                }
            }];
        }
        if ($productType->data($this->product) !== null) {
            $rules = array_merge($rules, $productType->data($this->product)->validate());
        }
        $configOptions = $this->product->configoptions()->orderBy('sort_order')->get();
        foreach ($configOptions as $configOption) {
            $rules['options.'.$configOption->key] = $configOption->validate();
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $this->validator = $validator;

        return $validator;
    }

    public function errors()
    {
        return $this->validator?->errors();
    }

    public function passes()
    {
        return ! $this->validator?->fails();
    }
}
