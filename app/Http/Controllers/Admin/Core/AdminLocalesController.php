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

namespace App\Http\Controllers\Admin\Core;

use App\Http\Controllers\Controller;
use App\Services\Core\LocaleService;

class AdminLocalesController extends Controller
{
    public function index()
    {
        $locales = LocaleService::getLocales(false);
        $card = app('settings')->getCards()->firstWhere('uuid', 'core');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'locales');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return view('admin.locales.index', compact('locales'));
    }

    public function download(string $locale)
    {
        $existing = LocaleService::getLocales(false)[$locale] ?? null;
        if (! $existing) {
            abort(404);
        }
        try {
            LocaleService::downloadFiles($locale);

            return back()->with('success', __('admin.locales.download_success'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function toggle(string $locale)
    {
        $existing = LocaleService::getLocales(false)[$locale] ?? null;
        if (! $existing) {
            abort(404);
        }
        LocaleService::toggleLocale($locale);

        return back();
    }
}
