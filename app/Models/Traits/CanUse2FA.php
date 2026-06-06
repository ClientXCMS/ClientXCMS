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

namespace App\Models\Traits;

use App\Mail\Auth\TwoFactorCodeEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FAQRCode\Google2FA;

trait CanUse2FA
{
    public function shouldForceTwoFactor(string $guard): bool
    {
        return in_array(setting($guard === 'admin' ? 'force_2fa_admin' : 'force_2fa_client', 'false'), ['true', true, 1, '1'], true);
    }

    public function twoFactorEnabled(): bool
    {
        return $this->hasMetadata('2fa_secret');
    }

    public function twoFactorDisable(): void
    {
        Session::put('2fa_verified', true);
        $this->detachMetadata('2fa_secret');
        $this->detachMetadata('2fa_recovery_codes');
    }

    public function twoFactorEnable(string $secret): void
    {
        Session::put('2fa_verified', true);
        $codes = $this->generateRecoveryCodes();
        $this->storeRecoveryCodes($codes);
        $this->attachMetadata('2fa_secret', $secret);
    }

    public function twoFactorEmailOnNewIpEnabled(): bool
    {
        return $this->getMetadata('2fa_email_new_ip') === 'true';
    }

    public function setTwoFactorEmailOnNewIp(bool $enabled): void
    {
        $this->attachMetadata('2fa_email_new_ip', $enabled ? 'true' : 'false');
    }

    public function shouldUseEmailTwoFactor(string $guard, ?string $ip = null): bool
    {
        return ($this->shouldForceTwoFactor($guard) && ! $this->twoFactorEnabled())
            || $this->requiresEmailTwoFactorForIp($ip);
    }

    public function requiresEmailTwoFactorForIp(?string $ip): bool
    {
        if (! $this->twoFactorEmailOnNewIpEnabled() || $ip === null) {
            return false;
        }

        return ! in_array($ip, $this->twoFactorTrustedIps(), true);
    }

    public function twoFactorTrustedIps(): array
    {
        $ips = json_decode($this->getMetadata('2fa_trusted_ips') ?: '[]', true);

        return is_array($ips) ? $ips : [];
    }

    public function trustTwoFactorIp(?string $ip): void
    {
        if ($ip === null) {
            return;
        }
        $ips = collect($this->twoFactorTrustedIps())
            ->push($ip)
            ->unique()
            ->take(-20)
            ->values()
            ->all();
        $this->attachMetadata('2fa_trusted_ips', json_encode($ips));
    }

    public function sendTwoFactorEmailCode(string $guard, ?string $ip = null): void
    {
        $expiresAt = now()->addMinutes(5);
        $metadataKey = '2fa_email_code_expires_at';

        if ($this->getMetadata($metadataKey) && now()->lt(\Carbon\Carbon::parse($this->getMetadata($metadataKey)))) {
            return;
        }

        $code = (string) random_int(100000, 999999);
        $this->attachMetadata('2fa_email_code', Hash::make($code));
        $this->attachMetadata($metadataKey, $expiresAt->toDateTimeString());
        $this->notify(new TwoFactorCodeEmail($code, $guard, $ip));
    }

    public function isValidEmailTwoFactorCode(string $code): bool
    {
        $hash = $this->getMetadata('2fa_email_code');
        $expiresAt = $this->getMetadata('2fa_email_code_expires_at');
        if (! $hash || ! $expiresAt || now()->gt(\Carbon\Carbon::parse($expiresAt))) {
            return false;
        }

        if (! Hash::check($code, $hash)) {
            return false;
        }

        $this->detachMetadata('2fa_email_code');
        $this->detachMetadata('2fa_email_code_expires_at');

        return true;
    }

    public function twoFactorRecoveryCodes(): array
    {
        if (! $this->hasMetadata('2fa_recovery_codes')) {
            $codes = $this->generateRecoveryCodes();
            $this->storeRecoveryCodes($codes);

            return $codes;
        }
        $raw = $this->getMetadata('2fa_recovery_codes');
        try {
            $raw = \Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            // Legacy plaintext metadata (pre-encryption fix). Use as-is;
            // it gets re-encrypted on the next storeRecoveryCodes() call.
        }

        return explode(',', $raw);
    }

    public function isValidRecoveryCode(string $code): bool
    {
        foreach ($this->twoFactorRecoveryCodes() as $stored) {
            if (is_string($stored) && hash_equals($stored, $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn () => $this->generateRecoveryCode())->all();
    }

    public function generateRecoveryCode(): string
    {
        return bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4));
    }

    public function useRecoveryCode(string $code): void
    {
        $remaining = collect($this->twoFactorRecoveryCodes())
            ->reject(fn ($stored) => is_string($stored) && hash_equals($stored, $code))
            ->values()
            ->all();
        $this->storeRecoveryCodes($remaining);
    }

    private function storeRecoveryCodes(array $codes): void
    {
        $this->attachMetadata('2fa_recovery_codes', \Crypt::encryptString(implode(',', $codes)));
    }

    public function isValidate2FA(string $code): bool
    {
        $code = str_replace(' ', '', $code);
        if ($this->isValidEmailTwoFactorCode($code)) {
            return true;
        }
        // v2.16 — SMS challenge runs in parallel to the email channel.
        if ($this->isValidSmsTwoFactorCode($code)) {
            return true;
        }
        $secret = $this->getMetadata('2fa_secret');
        if (! $secret) {
            return false;
        }

        if ((new Google2FA)->verifyKey($secret, $code)) {
            return true;
        }
        if ($this->isValidRecoveryCode($code)) {
            $this->useRecoveryCode($code);

            return true;
        }

        return false;
    }

    /**
     * v2.16 — Send a one-time code by SMS through the configured
     * gateway (see {@see \App\Services\Auth\SmsService}). Mirrors
     * sendTwoFactorEmailCode(): same 5-minute TTL, same bcrypt-hashed
     * persistence, same anti-resend rate-limit.
     *
     * Returns true when the SMS was attempted, false when the user
     * has no phone number on file or when the gateway threw.
     */
    public function sendTwoFactorSmsCode(string $guard, ?string $ip = null): bool
    {
        $phone = (string) ($this->phone ?? '');
        if ($phone === '') {
            return false;
        }

        $expiresAt = now()->addMinutes(5);
        $expiresKey = '2fa_sms_code_expires_at';

        if ($this->getMetadata($expiresKey)
            && now()->lt(\Carbon\Carbon::parse($this->getMetadata($expiresKey)))) {
            return true; // a previous code is still valid, don't re-send
        }

        $code = (string) random_int(100000, 999999);
        $this->attachMetadata('2fa_sms_code', Hash::make($code));
        $this->attachMetadata($expiresKey, $expiresAt->toDateTimeString());

        try {
            $appName = config('app.name', 'ClientXCMS');
            \App\Services\Auth\SmsService::gateway()->send(
                $phone,
                sprintf('%s — code de connexion : %s (valide 5 min)', $appName, $code)
            );

            return true;
        } catch (\Throwable $e) {
            logger()->warning('mfa.sms.send_failed', [
                'guard' => $guard,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            // Drop the metadata so the user can request a new code
            // without being rate-limited by a phantom one.
            $this->detachMetadata('2fa_sms_code');
            $this->detachMetadata($expiresKey);

            return false;
        }
    }

    public function isValidSmsTwoFactorCode(string $code): bool
    {
        $hash = $this->getMetadata('2fa_sms_code');
        $expiresAt = $this->getMetadata('2fa_sms_code_expires_at');
        if (! $hash || ! $expiresAt || now()->gt(\Carbon\Carbon::parse($expiresAt))) {
            return false;
        }

        if (! Hash::check($code, $hash)) {
            return false;
        }

        $this->detachMetadata('2fa_sms_code');
        $this->detachMetadata('2fa_sms_code_expires_at');

        return true;
    }

    public function twoFactorVerified(): bool
    {
        return Session::get('2fa_verified', false);
    }
}
