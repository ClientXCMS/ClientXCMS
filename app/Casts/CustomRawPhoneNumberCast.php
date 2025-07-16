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
namespace App\Casts;

use Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast;

class CustomRawPhoneNumberCast extends RawPhoneNumberCast
{
    public function get($model, string $key, $value, array $attributes)
    {
        try {
            return parent::get($model, $key, $value, $attributes);
        } catch (\InvalidArgumentException|\Error $e) {
            return $value;
        }
    }

    public function serialize($model, string $key, $value, array $attributes)
    {
        if (! $value || ! $value instanceof \Propaganistas\LaravelPhone\PhoneNumber) {
            return null;
        }

        return $value->getRawNumber();
    }
}
