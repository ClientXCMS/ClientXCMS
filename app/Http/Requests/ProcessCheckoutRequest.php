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


namespace App\Http\Requests;

use App\Models\Store\Basket\Basket;
use App\Services\Account\AccountEditService;
use Illuminate\Foundation\Http\FormRequest;

class ProcessCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = app(\App\Services\Core\PaymentTypeService::class)->all()->keys()->implode(',');
        $source = auth()->id() === null ? '' : auth()->user()->paymentMethods()->pluck('id')->implode(',');
        $rules = AccountEditService::rules($this->country ?? 'FR', false, false, auth()->id());
        if (setting('checkout_toslink', false)) {
            $rules['accept_tos'] = ['required', 'accepted'];
        }
        $rules['gateway'] = ['required', 'in:'.$types];
        $rules['paymentmethod'] = ['nullable', 'in:'.$source];

        return $rules;
    }

    protected function prepareForValidation()
    {
        if (Basket::getBasket()->total() == 0) {
            $this->merge(['gateway' => 'none']);
        }
        if (! $this->has('paymentmethod') || $this->get('paymentmethod') === 'none') {
            $this->merge(['paymentmethod' => null]);
        }
    }
}
