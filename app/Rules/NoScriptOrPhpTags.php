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

use Illuminate\Contracts\Validation\Rule;

class NoScriptOrPhpTags implements Rule
{
    public function passes($attribute, $value)
    {
        $content = file_get_contents($value->getRealPath());
        if (str_contains($content, '<script>') || str_contains($content, '<?php')) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'The :attribute contains forbidden script or PHP tags.';
    }
}
