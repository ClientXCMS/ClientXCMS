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

class Countries
{
    public static array $countries = [];

    public static function all()
    {
        if (empty(self::$countries)) {
            self::$countries = json_decode(file_get_contents(resource_path('countries.json'), true));
        }

        return self::$countries;
    }

    public static function names(): array
    {
        $names = [];
        foreach (self::all() as $country) {
            $names[$country->alpha_2_code] = $country->en_short_name;
        }

        return $names;
    }

    public static function rule()
    {
        $names = implode(',', array_keys(self::names()));

        return "phone:{$names}";
    }
}
