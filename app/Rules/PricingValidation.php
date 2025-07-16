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
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PricingValidation implements ValidationRule
{
    public function message()
    {
        return 'At least one price or setup fee must be set.';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $period => $pricing) {
            if (isset($pricing['price']) || isset($pricing['setup'])) {
                if (! is_null($pricing['price']) || ! is_null($pricing['setup'])) {
                    return;
                }
            }
        }
        $fail($this->message());
    }
}
