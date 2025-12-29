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


namespace App\Http\Requests\Customer;

use App\Services\Account\AccountEditService;
use App\Services\Core\LocaleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * @OA\Schema(
 *     schema="UpdateCustomerRequest",
 *
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", format="password", nullable=true, example="MyN3wS3cret!"),
 *     @OA\Property(property="firstname", type="string", maxLength=50, example="John"),
 *     @OA\Property(property="lastname", type="string", maxLength=50, example="Doe"),
 *     @OA\Property(property="address", type="string", maxLength=250, example="456 Avenue République"),
 *     @OA\Property(property="address2", type="string", maxLength=250, nullable=true, example="Bâtiment B"),
 *     @OA\Property(property="city", type="string", maxLength=250, example="Lyon"),
 *     @OA\Property(property="zipcode", type="string", maxLength=255, example="69000"),
 *     @OA\Property(property="phone", type="string", maxLength=255, nullable=true, example="+33700000000"),
 *     @OA\Property(property="region", type="string", maxLength=250, example="Auvergne-Rhône-Alpes"),
 *     @OA\Property(property="verified", type="boolean", nullable=true, example=false),
 *     @OA\Property(property="balance", type="number", format="float", example=250.50),
 *     @OA\Property(property="country", type="string", maxLength=255, example="FR"),
 *     @OA\Property(property="locale", type="string", maxLength=255, example="fr"),
 * *     @OA\Property(property="company_name", type="string", maxLength=255, nullable=true, example="Doe Industries"),
 *     @OA\Property(property="billing_details", type="string", maxLength=255, nullable=true, example="Details de facturation ici")
 * )
 */
class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->route('customer');

        // Use AccountEditService as base for common customer fields
        $baseRules = AccountEditService::rules(
            $this->country ?? 'FR',
            email: true,
            password: false,
            except: $customer?->id
        );

        // Merge with admin-specific rules
        return array_merge($baseRules, [
            'verified' => ['nullable', 'boolean'],
            'balance' => ['numeric', 'min:0', 'max:999999'],
            'password' => ['nullable', 'string', 'min:8', Rules\Password::defaults()],
            'locale' => ['string', 'max:255', Rule::in(array_keys(LocaleService::getLocalesNames()))],
        ]);
    }

    public function update()
    {
        $this->validated();
        $customer = $this->route('customer');
        $customer->update($this->validated());
        if ($this->filled('password')) {
            $customer->password = Hash::make($this->input('password'));
            $customer->save();
        }

        return $customer;
    }
}
