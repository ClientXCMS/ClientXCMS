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
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\RequiredIf;

class ProfilePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user('web');

        return [
            'password' => ['required', 'confirmed', Password::default()],
            'currentpassword' => ['required', 'current_password'],
            '2fa' => [new RequiredIf($user->twoFactorEnabled()), 'string', 'size:6', new Valid2FACodeRule],
            'security_answer' => [new RequiredIf($user->hasSecurityQuestion()), 'nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user('web');

            if ($user->hasSecurityQuestion() && $this->filled('security_answer')) {
                if (!$user->verifySecurityAnswer($this->security_answer)) {
                    $validator->errors()->add('security_answer', __('client.profile.security_answer_invalid'));
                }
            }
        });
    }
}
