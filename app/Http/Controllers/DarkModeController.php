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

namespace App\Http\Controllers;

class DarkModeController extends Controller
{
    public function darkmode()
    {
        $last = \Illuminate\Support\Facades\Session::get('dark_mode', 0);
        if ($last == 0) {
            \Illuminate\Support\Facades\Session::put('dark_mode', 1);
        } else {
            \Illuminate\Support\Facades\Session::forget('dark_mode');
        }
        if (auth()->check()) {
            $user = auth()->user();
            $user->dark_mode = ! $user->dark_mode;
            $user->save();
        }
    }

    public function darkmodeAdmin()
    {
        if (auth('admin')->check()) {
            $user = auth('admin')->user();
            $user->dark_mode = ! $user->dark_mode;
            $user->save();
        }

        return response()->noContent();
    }
}
