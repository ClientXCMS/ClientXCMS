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
namespace App\Http\Controllers;

use App\Services\Core\LocaleService;

class LocaleController extends Controller
{
    public function setLocale(string $locale)
    {
        if (in_array($locale, array_keys(LocaleService::getLocales()))) {
            return LocaleService::saveLocale($locale);
        }

        return redirect()->back();
    }
}
