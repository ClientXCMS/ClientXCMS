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


namespace App\Http\Requests\Profile;

use App\Helpers\Countries;
use App\Rules\ZipCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['string', 'max:255'],
            'address' => ['string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['string', 'max:255'],
            'zipcode' => ['string', 'max:255', new ZipCode($this->country)],
            'phone' => ['max:255', Countries::rule(), Rule::unique('customers', 'phone')->ignore($this->user('web')->id)],
            'region' => ['string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'billing_details' => ['nullable', 'string', 'max:255'],
            'country' => ['string', 'max:255', Rule::in(array_keys(Countries::names()))],
            'locale' => ['string', 'max:255', Rule::in(array_keys(\App\Services\Core\LocaleService::getLocalesNames()))],
        ];
    }
}
