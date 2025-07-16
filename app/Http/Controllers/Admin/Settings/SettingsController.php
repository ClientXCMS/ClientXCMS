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
namespace App\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;

class SettingsController
{
    public function index()
    {
        $cards = app('settings')->getCards();

        return view('admin.settings.index', compact('cards'));
    }

    public function show(string $card, string $uuid, Request $request)
    {
        $card = app('settings')->getCards()->firstWhere('uuid', $card);
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', $uuid);
        \View::share('current_card', $card);
        \View::share('current_item', $item);
        if (! $item) {
            abort(404);
        }
        if (! staff_has_permission($item->permission)) {
            abort(403);
        }
        if (is_callable($item->action)) {
            return $item->action();
        }
        if (is_string($item->action)) {
            return redirect()->intended($item->action);
        }
        if (is_array($item->action)) {
            return app($item->action[0])->{$item->action[1]}($request);
        }
        abort(404);
    }
}
