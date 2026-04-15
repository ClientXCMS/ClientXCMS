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

use App\Rules\Valid2FACodeRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', 'current_password:web'],
            'confirm_deletion' => ['required', 'accepted'],
        ];

        // If 2FA is enabled, require 2FA code
        if ($this->user('web')->twoFactorEnabled()) {
            $rules['2fa_code'] = [
                'required',
                'string',
                new Valid2FACodeRule($this->user('web')->getMetadata('2fa_secret')),
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.current_password' => __('client.profile.delete.invalid_password'),
            'confirm_deletion.accepted' => __('client.profile.delete.must_confirm'),
            '2fa_code.required' => __('client.profile.delete.2fa_required'),
        ];
    }
}
