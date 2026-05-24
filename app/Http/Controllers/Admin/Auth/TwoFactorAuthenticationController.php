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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin-side mirror of the customer two-factor flow. Same state machine,
 * same factor requirements, different guard and redirect targets.
 *
 * See app/Http/Controllers/Auth/TwoFactorAuthenticationController.php for
 * the design rationale.
 */
class TwoFactorAuthenticationController
{
    public function show(Request $request)
    {
        $user = $request->user('admin');
        if (! $user) {
            return redirect()->route('admin.login');
        }

        $needs = $this->factorRequirements($user, $request->ip());
        $totpDone = $request->session()->get('2fa_totp_verified', false);

        if ($needs['totp'] && ! $totpDone) {
            return view('admin.auth.2fa', [
                'step' => 'totp',
                'requiresEmailAfter' => $needs['email'],
            ]);
        }

        if ($needs['email']) {
            $user->sendTwoFactorEmailCode('admin', $request->ip());

            return view('admin.auth.2fa', [
                'step' => 'email',
                'requiresTotpBefore' => $needs['totp'],
            ]);
        }

        return view('admin.auth.2fa', ['step' => 'totp']);
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

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget('2fa_totp_verified');

        return redirect()->route('admin.auth.2fa');
    }

    public function verify(Request $request)
    {
        $request->validate([
            '2fa' => ['required', 'string', 'max:64', new Valid2FACodeInput],
            'trust_device' => ['nullable'],
        ]);

        $user = auth('admin')->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }

        $code = $request->input('2fa');
        $needs = $this->factorRequirements($user, $request->ip());
        $totpDone = $request->session()->get('2fa_totp_verified', false);

        if ($needs['totp'] && ! $totpDone) {
            return $this->handleDeviceStep($request, $user, $code, $needs['email']);
        }

        if ($needs['email']) {
            return $this->handleEmailStep($request, $user, $code);
        }

        return redirect()->route('admin.auth.2fa');
    }

    private function factorRequirements($user, ?string $ip): array
    {
        $totpEnabled = $user->twoFactorEnabled();
        $emailRequired = ($user->shouldForceTwoFactor('admin') && ! $totpEnabled)
            || $user->requiresEmailTwoFactorForIp($ip);

        return [
            'totp' => $totpEnabled,
            'email' => $emailRequired,
        ];
    }

    private function handleDeviceStep(Request $request, $user, string $code, bool $emailWillFollow): RedirectResponse
    {
        if (! $user->verifyDeviceFactor($code)) {
            return redirect()->route('admin.auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
        }

        $request->session()->put('2fa_totp_verified', true);

        if (! $emailWillFollow) {
            return $this->completeFlow($request, $user);
        }

        return redirect()->route('admin.auth.2fa');
    }

    private function handleEmailStep(Request $request, $user, string $code): RedirectResponse
    {
        if (! $user->isValidEmailTwoFactorCode(str_replace(' ', '', $code))) {
            return redirect()->route('admin.auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
        }

        return $this->completeFlow($request, $user);
    }

    private function completeFlow(Request $request, $user): RedirectResponse
    {
        session()->regenerate();
        \Session::put('2fa_verified', true);
        $request->session()->forget('2fa_totp_verified');

        if ($request->boolean('trust_device')) {
            $user->trustTwoFactorIp($request->ip());
        }

        return redirect()->intended(admin_prefix('dashboard'));
    }
}
