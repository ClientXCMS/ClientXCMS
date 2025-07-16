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
namespace App\Http\Requests\Customer;

use App\Helpers\Countries;
use App\Rules\ZipCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Propaganistas\LaravelPhone\Rules\Phone;

/**
 * @OA\Schema(
 *     schema="StoreCustomerRequest",
 *     required={"email", "firstname", "lastname", "address", "city", "zipcode", "region", "country", "locale"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", format="password", nullable=true, example="MyS3cretPwd!"),
 *     @OA\Property(property="firstname", type="string", maxLength=255, example="John"),
 *     @OA\Property(property="lastname", type="string", maxLength=255, example="Doe"),
 *     @OA\Property(property="address", type="string", maxLength=255, example="123 Rue Principale"),
 *     @OA\Property(property="address2", type="string", maxLength=255, nullable=true, example="Appartement 4B"),
 *     @OA\Property(property="city", type="string", maxLength=255, example="Paris"),
 *     @OA\Property(property="zipcode", type="string", maxLength=255, example="75000"),
 *     @OA\Property(property="phone", type="string", maxLength=15, nullable=true, example="+33600000000"),
 *     @OA\Property(property="region", type="string", maxLength=255, example="Île-de-France"),
 *     @OA\Property(property="verified", type="boolean", nullable=true, example=true),
 *     @OA\Property(property="balance", type="number", format="float", example=99.99),
 *     @OA\Property(property="locale", type="string", example="fr"),
 *     @OA\Property(property="country", type="string", example="FR"),
 *     @OA\Property(property="confirmed", type="boolean", nullable=true, example=true)
 * )
 */
class StoreCustomerRequest extends FormRequest
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
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:customers'],
            'password' => ['nullable', Rules\Password::defaults()],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', Countries::rule(), 'max:15', (new Phone)->country($this->input('country')), Rule::unique('customers', 'phone')->ignore($this->id)],
            'zipcode' => ['required', 'string', 'max:255', new ZipCode($this->input('country'))],
            'region' => ['required', 'string', 'max:255'],
            'verified' => ['nullable', 'boolean'],
            'balance' => ['numeric', 'min:0', 'max:9999999999'],
            'locale' => ['required', 'string', 'max:255', Rule::in(array_keys(\App\Services\Core\LocaleService::getLocalesNames()))],
            'country' => ['required', 'string', 'max:255', Rule::in(array_keys(Countries::names()))],
        ];
    }

    public function store()
    {
        $data = $this->validated();
        if (! $this->filled('password')) {
            $data = array_merge($data, [
                'password' => \Illuminate\Support\Str::random(10),
            ]);
        }
        $data = array_merge($data, [
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);
        $customer = \App\Models\Account\Customer::create($data);
        if (! $this->filled('password')) {
            Password::broker('users')->sendResetLink(['email' => $customer->email]);
        }
        if ($this->filled('verified')) {
            $customer->markEmailAsVerified();
        }

        return $customer;
    }
}
