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

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class Countries
{
    private const ENABLED_COUNTRIES_PATH = 'enabled_countries.json';

    private const DEFAULT_ENABLED_COUNTRIES = [
        'FR',
        'BE',
        'CH',
        'LU',
        'CA',
        'US',
        'GB',
        'DE',
        'ES',
        'IT',
        'PT',
        'NL',
        'IE',
        'AT',
        'MA',
        'DZ',
        'TN',
        'SN',
        'CI',
        'CM',
    ];

    public static array $countries = [];

    public static function all()
    {
        if (empty(self::$countries)) {
            self::$countries = json_decode(file_get_contents(resource_path('countries.json')));
        }

        return self::$countries;
    }

    public static function allNames(): array
    {
        $names = [];
        foreach (self::all() as $country) {
            $names[$country->alpha_2_code] = $country->en_short_name;
        }

        return $names;
    }

    public static function names(): array
    {
        return array_intersect_key(self::allNames(), array_flip(self::enabledCodes()));
    }

    public static function enabledCodes(): array
    {
        if (! Storage::exists(self::ENABLED_COUNTRIES_PATH)) {
            return self::defaultEnabledCodes();
        }

        $codes = json_decode(Storage::get(self::ENABLED_COUNTRIES_PATH), true);
        if (! is_array($codes)) {
            return self::defaultEnabledCodes();
        }

        return array_values(array_intersect($codes, array_keys(self::allNames())));
    }

    public static function setEnabledCodes(array $codes): void
    {
        $codes = array_values(array_intersect($codes, array_keys(self::allNames())));

        Storage::put(self::ENABLED_COUNTRIES_PATH, json_encode($codes));
    }

    private static function defaultEnabledCodes(): array
    {
        return array_values(array_intersect(self::DEFAULT_ENABLED_COUNTRIES, array_keys(self::allNames())));
    }

    public static function rule()
    {
        $names = implode(',', array_keys(self::names()));

        return "phone:{$names}";
    }

    public static function getName(string $code): string
    {
        return self::names()[$code] ?? '';
    }
}
