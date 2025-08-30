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

class NotContainRule implements ValidationRule
{

    private array $strings = [];

    public function __construct(array $strings = [])
    {
        $this->strings = $strings;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->strings as $string) {
            if (str_contains($value, $string)) {
                $fail("The :attribute field must not contain the value: {$string}.");
                return;
            }
        }
    }
}
