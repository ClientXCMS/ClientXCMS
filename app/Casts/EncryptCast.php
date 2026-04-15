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

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        try {
            return ! is_null($value) ? decrypt($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return [$key => ! is_null($value) ? encrypt($value) : null];
    }
}
