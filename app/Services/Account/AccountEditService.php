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

namespace App\Services\Account;

use App\Helpers\Countries;
use App\Rules\PhoneRule;
use App\Rules\ZipCode;
use App\Services\Core\LocaleService;
use Illuminate\Validation\Rule;

class AccountEditService
{
    /**
     * Get validation rules for customer data.
     *
     * @param  string  $country  The country code for phone/zipcode validation
     * @param  bool  $email  Whether to include email validation rules
     * @param  bool  $password  Whether to include password validation rules
     * @param  int|null  $except  Customer ID to exclude from unique checks
     */
    public static function rules(string $country, bool $email = false, bool $password = false, ?int $except = null): array
    {
        $phoneRule = (new PhoneRule)->country(...array_keys(Countries::names()));

        $rules = [
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:250'],
            'address2' => ['nullable', 'string', 'max:250'],
            'city' => ['required', 'string', 'max:250'],
            'phone' => ['nullable', 'max:30', $phoneRule, Rule::unique('customers', 'phone')->ignore($except)],
            'zipcode' => ['required', 'string', 'max:255', new ZipCode($country)],
            'region' => ['required', 'string', 'max:250'],
            'country' => ['required', 'string', 'max:255', Rule::in(array_keys(Countries::names()))],
            'company_name' => ['nullable', 'string', 'max:255'],
            'billing_details' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:255', Rule::in(array_keys(LocaleService::getLocalesNames()))],
        ];
        if ($email) {
            $rules['email'] = [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($except),
                function ($attribute, $value, $fail) {
                    if (str_contains($value, '+') && ! setting('allow_plus_in_email', false)) {
                        $fail('The :attribute must not contain the character "+".');
                    }
                },
            ];
        }
        if ($password) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    public static function saveCurrentCustomer(array $all): bool
    {
        $filtred = [
            'firstname' => $all['firstname'],
            'lastname' => $all['lastname'],
            'address' => $all['address'],
            'address2' => $all['address2'],
            'city' => $all['city'],
            'zipcode' => $all['zipcode'],
            'phone' => $all['phone'],
            'region' => $all['region'],
            'country' => $all['country'],
            'company_name' => $all['company_name'] ?? null,
            'billing_details' => $all['billing_details'] ?? null,
        ];
        if (isset($all['locale']) && LocaleService::isValideLocale($all['locale'])) {
            $filtred['locale'] = $all['locale'];
        }
        $customer = auth('web')->user();

        return $customer->update($filtred);
    }
}
