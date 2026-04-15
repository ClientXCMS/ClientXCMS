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
use DOMDocument;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidHtmlWithoutBlade implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/@[\w]+|{{.*?}}|{!!.*?!!}/s', $value)) {
            $fail(__('The content must not contain Blade directives.'));
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;

        $wrappedValue = '<!DOCTYPE html><html><body>'.$value.'</body></html>';
        $loaded = $dom->loadHTML($wrappedValue, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if (! $loaded) {
            $fail(__('The content must be valid HTML.'));
        }
    }
}
