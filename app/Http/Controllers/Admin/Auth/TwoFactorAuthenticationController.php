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

namespace App\Http\Controllers\Admin\Auth;

use App\Rules\Valid2FACodeInput;
use Illuminate\Http\Request;

class TwoFactorAuthenticationController
{
    public function show(Request $request)
    {
        $user = $request->user('admin');
        if (! $user) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.2fa');
    }

    public function sendEmailCode(Request $request)
    {
        $user = $request->user('admin');
        if (! $user) {
            return redirect()->route('admin.login');
        }
        if (! $user->shouldUseEmailTwoFactor('admin', $request->ip())) {
            return redirect()->route('admin.auth.2fa');
        }

        $user->sendTwoFactorEmailCode('admin', $request->ip());

        return redirect()->route('admin.auth.2fa')->with('success', __('client.profile.2fa.email_sent'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            '2fa' => ['required', 'string', 'max:64', new Valid2FACodeInput],
        ]);
        $user = auth('admin')->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }
        if ($user->isValidate2FA($request->input('2fa'))) {
            session()->regenerate();
            \Session::put('2fa_verified', true);
            $user->trustTwoFactorIp($request->ip());

            return redirect()->intended(admin_prefix('dashboard'));
        }

        return redirect()->route('admin.auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
    }
}
