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

class GDPRController
{
    public function gdpr()
    {
        $last = \Illuminate\Support\Facades\Session::get('gdpr_compliment', 0);
        if ($last == 0) {
            \Illuminate\Support\Facades\Session::put('gdpr_compliment', 1);
        } else {
            \Illuminate\Support\Facades\Session::forget('gdpr_compliment');
        }
        if (auth()->check()) {
            $user = auth()->user();
            $user->gdpr_compliment = !$user->gdpr_compliment;
            $user->save();
        }
    }
}
