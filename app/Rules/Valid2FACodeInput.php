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

// Cheap format gate before bcrypt + Google2FA. Accepts 6 digits (TOTP/email)
// or 8hex-8hex-8hex (recovery). Whitespace tolerated, fails fast otherwise.
class Valid2FACodeInput implements ValidationRule
{
    private const TOTP_OR_EMAIL = '/^[0-9]{6}$/';

    private const RECOVERY = '/^[0-9a-f]{8}-[0-9a-f]{8}-[0-9a-f]{8}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.2fa_code'));

            return;
        }

        $normalized = str_replace(' ', '', $value);

        if (preg_match(self::TOTP_OR_EMAIL, $normalized) === 1) {
            return;
        }

        if (preg_match(self::RECOVERY, strtolower($normalized)) === 1) {
            return;
        }

        $fail(__('validation.2fa_code'));
    }
}
