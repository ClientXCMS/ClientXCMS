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

namespace App\Http\Controllers\Auth;

use App\Rules\Valid2FACodeRule;
use Illuminate\Http\Request;

class TwoFactorAuthenticationController
{
    public function show()
    {
        return view('front.auth.2fa');
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            '2fa' => ['required', 'string', new Valid2FACodeRule],
        ]);
        if (auth()->user()->isValidate2FA($request->input('2fa'))) {
            \Session::put('2fa_verified', true);

            return redirect()->intended('/client');
        }

        return redirect()->route('auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
    }
}
