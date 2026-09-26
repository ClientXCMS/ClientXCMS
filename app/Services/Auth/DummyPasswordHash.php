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

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Hash of a random throwaway password, checked when the account is unknown so both paths cost one full hash.
class DummyPasswordHash
{
    private const CACHE_KEY = 'auth.dummy_password_hash';

    public static function get(): string
    {
        $hash = Cache::get(self::CACHE_KEY);
        if (is_string($hash) && ! Hash::needsRehash($hash)) {
            return $hash;
        }

        $hash = Hash::make(Str::random(40));
        Cache::forever(self::CACHE_KEY, $hash);

        return $hash;
    }
}
