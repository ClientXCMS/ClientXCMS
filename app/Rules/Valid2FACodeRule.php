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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;

class Valid2FACodeRule implements ValidationRule
{
    private ?string $secret = null;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $google = new Google2FA;
        $user = auth('web')->user() ?? auth('admin')->user();
        $secret = $this->secret ?: $user->getMetadata('2fa_secret');
        try {
            if (! $google->verifyKey($secret, $value)) {
                $fail(__('validation.2fa_code'));
            }
        } catch (IncompatibleWithGoogleAuthenticatorException|InvalidCharactersException|SecretKeyTooShortException $e) {
            $fail(__('validation.2fa_code'));
        }
    }
}
