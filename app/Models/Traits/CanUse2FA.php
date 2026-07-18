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
use App\Services\Auth\MfaConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FAQRCode\Google2FA;

trait CanUse2FA
{
    public function shouldForceTwoFactor(string $guard): bool
    {
        return MfaConfig::forceFor($guard);
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

    /** @deprecated Use {@see MfaConfig::trustedDevicesMax()}. Kept for BC. */
    public const TRUST_IP_MAX = 20;

    public function requiresEmailTwoFactorForIp(?string $ip): bool
    {
        if (! $this->twoFactorEmailOnNewIpEnabled() || $ip === null) {
            return false;
        }

        $trustedIps = array_column($this->twoFactorTrustedIps(), 'ip');

        return ! in_array($ip, $trustedIps, true);
    }

    public function twoFactorTrustedIps(): array
    {
        $raw = $this->getMetadata('2fa_trusted_ips');
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function ($entry) {
                if (is_string($entry)) {
                    return ['ip' => $entry, 'until' => null, 'user_agent' => null];
                }
                if (is_array($entry) && isset($entry['ip']) && is_string($entry['ip'])) {
                    $until = $entry['until'] ?? null;
                    $ua = $entry['user_agent'] ?? null;

                    return [
                        'ip' => $entry['ip'],
                        'until' => is_string($until) ? $until : null,
                        'user_agent' => is_string($ua) ? $ua : null,
                    ];
                }

                return null;
            })
            ->filter()
            ->reject(fn (array $entry) => $entry['until'] !== null && now()->gt(\Carbon\Carbon::parse($entry['until'])))
            ->values()
            ->all();
    }

    // Returns remaining count (used by caller for "N left" toast). No-op if IP not in list.
    public function revokeTwoFactorTrust(string $ip): int
    {
        $entries = collect($this->twoFactorTrustedIps())
            ->reject(fn (array $entry) => $entry['ip'] === $ip)
            ->values()
            ->all();

        $this->attachMetadata('2fa_trusted_ips', json_encode($entries));

        return count($entries);
    }

    // Called on compromise signals: password change, reset, explicit "revoke all".
    public function revokeAllTwoFactorTrust(): void
    {
        $this->detachMetadata('2fa_trusted_ips');
    }

    public function trustTwoFactorIp(?string $ip, ?string $userAgent = null): void
    {
        if ($ip === null) {
            return;
        }

        $until = now()->addDays(MfaConfig::trustedDeviceLifetimeDays())->toDateTimeString();
        // Defensive bound: a malicious UA can be megabytes long.
        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;

        $entries = collect($this->twoFactorTrustedIps())
            ->reject(fn (array $entry) => $entry['ip'] === $ip)
            ->push(['ip' => $ip, 'until' => $until, 'user_agent' => $ua])
            ->take(-MfaConfig::trustedDevicesMax())
            ->values()
            ->all();

        $this->attachMetadata('2fa_trusted_ips', json_encode($entries));
    }

    /** @deprecated Use {@see MfaConfig::emailMaxAttempts()}. Kept for BC. */
    public const EMAIL_2FA_MAX_ATTEMPTS = 5;

    /** @deprecated Use {@see MfaConfig::emailMaxCycles()}. Kept for BC. */
    public const EMAIL_2FA_MAX_CYCLES = 3;

    /** @deprecated Use {@see MfaConfig::emailCooldownMinutes()}. Kept for BC. */
    public const EMAIL_2FA_COOLDOWN_MINUTES = 5;

    public function sendTwoFactorEmailCode(string $guard, ?string $ip = null): void
    {
        if ($this->isEmailTwoFactorOnCooldown()) {
            return;
        }

        $expiresAt = now()->addMinutes(MfaConfig::emailCodeTtlMinutes());
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

        if (! $hash || ! $expiresAt) {
            return false;
        }

        if (now()->gt(\Carbon\Carbon::parse($expiresAt))) {
            $this->clearEmailTwoFactorCode();

            return false;
        }

        if (! Hash::check($code, $hash)) {
            $attempts = (int) ($this->getMetadata('2fa_email_code_attempts') ?: 0) + 1;
            if ($attempts >= MfaConfig::emailMaxAttempts()) {
                $this->clearEmailTwoFactorCode();
                $this->markEmailTwoFactorCycleBurned();
            } else {
                $this->attachMetadata('2fa_email_code_attempts', (string) $attempts);
            }

            return false;
        }

        $this->clearEmailTwoFactorCode();
        $this->resetEmailTwoFactorBurnedCycles();

        return true;
    }

    public function isEmailTwoFactorOnCooldown(): bool
    {
        $burned = (int) ($this->getMetadata('2fa_email_burned_cycles') ?: 0);
        if ($burned < MfaConfig::emailMaxCycles()) {
            return false;
        }

        $burnedAt = $this->getMetadata('2fa_email_burned_at');
        if (! $burnedAt) {
            return false;
        }

        if (now()->gte(\Carbon\Carbon::parse($burnedAt)->addMinutes(MfaConfig::emailCooldownMinutes()))) {
            $this->resetEmailTwoFactorBurnedCycles();

            return false;
        }

        return true;
    }

    private function markEmailTwoFactorCycleBurned(): void
    {
        $burned = (int) ($this->getMetadata('2fa_email_burned_cycles') ?: 0) + 1;
        $this->attachMetadata('2fa_email_burned_cycles', (string) $burned);
        $this->attachMetadata('2fa_email_burned_at', now()->toDateTimeString());
    }

    private function resetEmailTwoFactorBurnedCycles(): void
    {
        $this->detachMetadata('2fa_email_burned_cycles');
        $this->detachMetadata('2fa_email_burned_at');
    }

    private function clearEmailTwoFactorCode(): void
    {
        $this->detachMetadata('2fa_email_code');
        $this->detachMetadata('2fa_email_code_expires_at');
        $this->detachMetadata('2fa_email_code_attempts');
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

    /** @deprecated Use {@see MfaConfig::smsDailyCap()}. Kept for BC. */
    public const SMS_2FA_DAILY_CAP = 10;

    // Send OTP via configured SMS gateway. Mirrors sendTwoFactorEmailCode.
    public function sendTwoFactorSmsCode(string $guard, ?string $ip = null): bool
    {
        $phone = (string) ($this->phone ?? '');
        // E.164: leading '+', country code 1-9, total 8-15 digits.
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            return false;
        }

        // Daily cap (rolling 24h). Resets when the window expires.
        $dailyKey = '2fa_sms_daily_count';
        $resetKey = '2fa_sms_daily_reset_at';
        $reset = $this->getMetadata($resetKey);
        $count = (int) ($this->getMetadata($dailyKey) ?: 0);
        if ($reset && now()->gt(\Carbon\Carbon::parse($reset))) {
            $count = 0;
            $this->detachMetadata($resetKey);
            $this->detachMetadata($dailyKey);
        }
        if ($count >= MfaConfig::smsDailyCap()) {
            logger()->warning('mfa.sms.daily_cap_hit', [
                'guard' => $guard,
                'user_id' => $this->id,
            ]);

            return false;
        }

        $expiresAt = now()->addMinutes(MfaConfig::smsCodeTtlMinutes());
        $expiresKey = '2fa_sms_code_expires_at';

        if ($this->getMetadata($expiresKey)
            && now()->lt(\Carbon\Carbon::parse($this->getMetadata($expiresKey)))) {
            return true;
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

            $this->attachMetadata($dailyKey, (string) ($count + 1));
            if (! $reset) {
                $this->attachMetadata($resetKey, now()->addDay()->toDateTimeString());
            }

            return true;
        } catch (\Throwable $e) {
            logger()->warning('mfa.sms.send_failed', [
                'guard' => $guard,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
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

    // Device-bound factor only (TOTP/recovery). Step 1 of two-step; email
    // alone must never satisfy "something you have" when TOTP is set up.
    public function verifyDeviceFactor(string $code): bool
    {
        $code = str_replace(' ', '', $code);
        $secret = $this->getMetadata('2fa_secret');

        if ($secret && (new Google2FA)->verifyKey($secret, $code)) {
            return true;
        }

        if ($this->isValidRecoveryCode($code)) {
            $this->useRecoveryCode($code);

            return true;
        }

        return false;
    }

    public function twoFactorVerified(): bool
    {
        return Session::get('2fa_verified', false);
    }
}
