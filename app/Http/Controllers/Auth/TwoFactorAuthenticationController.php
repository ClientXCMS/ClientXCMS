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

use App\Rules\Valid2FACodeInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Two-factor verification flow for customers.
 *
 * Implements the v2.16 audit F1 fix: on an untrusted IP (when
 * `2fa_email_new_ip` is enabled) and with TOTP active, the user must
 * satisfy BOTH the device factor (TOTP or recovery) AND the email
 * factor in two successive steps. A valid email code alone never
 * suffices when TOTP is enabled.
 *
 * Session state machine:
 *   - 2fa_totp_verified : true once step 1 succeeds (kept across GETs)
 *   - 2fa_verified      : true once the full flow completes (read by
 *                         Validate2FAMiddleware)
 *
 * Single-factor flows (TOTP only on trusted IP, or email-only when
 * TOTP isn't set up) collapse to one step and behave exactly as before.
 */
class TwoFactorAuthenticationController
{
    public function show(Request $request)
    {
        $user = $request->user('web');
        if (! $user) {
            return redirect()->route('login');
        }

        $needs = $this->factorRequirements($user, $request->ip());
        $totpDone = $request->session()->get('2fa_totp_verified', false);

        if ($needs['totp'] && ! $totpDone) {
            return view('front.auth.2fa', [
                'factorStep' => 'totp',
                'requiresEmailAfter' => $needs['email'],
            ]);
        }

        if ($needs['email']) {
            // Idempotent: sendTwoFactorEmailCode early-returns if an active
            // code is still in the validity window, so refreshing the page
            // does not spam the user with new emails.
            $user->sendTwoFactorEmailCode('web', $request->ip());

            return view('front.auth.2fa', [
                'factorStep' => 'email',
                'requiresTotpBefore' => $needs['totp'],
                'maskedEmail' => $this->maskEmail($user->email),
            ]);
        }

        // Middleware bug fallback - should never reach here if 2FA was required.
        return view('front.auth.2fa', ['step' => 'totp']);
    }

    public function sendEmailCode(Request $request)
    {
        $user = $request->user('web');
        if (! $user->shouldUseEmailTwoFactor('web', $request->ip())) {
            return redirect()->route('auth.2fa');
        }

        $user->sendTwoFactorEmailCode('web', $request->ip());

        return redirect()->route('auth.2fa')->with('success', __('client.profile.2fa.email_sent'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget('2fa_totp_verified');

        return redirect()->route('auth.2fa');
    }

    public function verify(Request $request)
    {
        $request->validate([
            '2fa' => ['required', 'string', 'max:64', new Valid2FACodeInput],
            'trust_device' => ['nullable'],
        ]);

        $user = $request->user('web');
        if (! $user) {
            return redirect()->route('login');
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

        // Edge: neither factor required - middleware should have let through already.
        return redirect()->route('auth.2fa');
    }

    /**
     * Mask the local part of an email for shoulder-surfing resistance.
     *   alex@cerbonix.eu -> a**x@cerbonix.eu
     *   ab@x.com         -> **@x.com
     */
    private function maskEmail(string $email): string
    {
        $at = strpos($email, '@');
        if ($at === false) {
            return $email;
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at);

        if (strlen($local) <= 2) {
            return str_repeat('*', strlen($local)).$domain;
        }

        return $local[0].str_repeat('*', strlen($local) - 2).substr($local, -1).$domain;
    }

    /**
     * Computes which factors are required for this user on this IP.
     * Mirrors the gates in Validate2FAMiddleware so the flow is consistent.
     */
    private function factorRequirements($user, ?string $ip): array
    {
        $totpEnabled = $user->twoFactorEnabled();
        $emailRequired = ($user->shouldForceTwoFactor('web') && ! $totpEnabled)
            || $user->requiresEmailTwoFactorForIp($ip);

        return [
            'totp' => $totpEnabled,
            'email' => $emailRequired,
        ];
    }

    private function handleDeviceStep(Request $request, $user, string $code, bool $emailWillFollow): RedirectResponse
    {
        if (! $user->verifyDeviceFactor($code)) {
            return redirect()->route('auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
        }

        $request->session()->put('2fa_totp_verified', true);

        if (! $emailWillFollow) {
            // Single-factor flow: TOTP alone is enough.
            return $this->completeFlow($request, $user);
        }

        return redirect()->route('auth.2fa');
    }

    private function handleEmailStep(Request $request, $user, string $code): RedirectResponse
    {
        if (! $user->isValidEmailTwoFactorCode(str_replace(' ', '', $code))) {
            return redirect()->route('auth.2fa')->withErrors(['2fa' => __('validation.2fa_code')]);
        }

        return $this->completeFlow($request, $user);
    }

    private function completeFlow(Request $request, $user): RedirectResponse
    {
        $request->session()->regenerate();
        \Session::put('2fa_verified', true);
        $request->session()->forget('2fa_totp_verified');

        if ($request->boolean('trust_device')) {
            $user->trustTwoFactorIp($request->ip());
        }

        return redirect()->intended('/client');
    }
}
