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

    /**
     * Soft cap on trusted device entries. Prevents unbounded metadata growth
     * if a user roams across many networks; oldest entries are evicted first.
     */
    public const TRUST_IP_MAX = 20;

    public function requiresEmailTwoFactorForIp(?string $ip): bool
    {
        if (! $this->twoFactorEmailOnNewIpEnabled() || $ip === null) {
            return false;
        }

        $trustedIps = array_column($this->twoFactorTrustedIps(), 'ip');

        return ! in_array($ip, $trustedIps, true);
    }

    /**
     * Returns active trusted entries as
     *   [['ip' => ..., 'until' => ISO|null, 'user_agent' => string|null], ...]
     *
     * Legacy rows are surfaced with safe defaults so older entries do not
     * crash the view:
     *   - bare IP strings (pre v2.16-audit)        -> until=null, user_agent=null
     *   - {ip, until} entries (post F1.1, pre F3.0) -> user_agent=null
     *
     * Expired entries are filtered out at read time.
     */
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

    /**
     * Drop a single trusted entry. Returns the remaining count so the caller
     * can flash a confirmation message ("device revoked, N left"). No-op if
     * the IP is not in the list.
     */
    public function revokeTwoFactorTrust(string $ip): int
    {
        $entries = collect($this->twoFactorTrustedIps())
            ->reject(fn (array $entry) => $entry['ip'] === $ip)
            ->values()
            ->all();

        $this->attachMetadata('2fa_trusted_ips', json_encode($entries));

        return count($entries);
    }

    /**
     * Wipe the trusted-device list. Called whenever the user takes an action
     * that implies "the account may be compromised": password change, password
     * reset, or explicit "revoke all" click.
     */
    public function revokeAllTwoFactorTrust(): void
    {
        $this->detachMetadata('2fa_trusted_ips');
    }

    public function trustTwoFactorIp(?string $ip, ?string $userAgent = null): void
    {
        if ($ip === null) {
            return;
        }

        $days = max(1, (int) setting('trust_device_days', 30));
        $until = now()->addDays($days)->toDateTimeString();
        // Defensive bound: a malicious UA can be megabytes long.
        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;

        $entries = collect($this->twoFactorTrustedIps())
            ->reject(fn (array $entry) => $entry['ip'] === $ip)
            ->push(['ip' => $ip, 'until' => $until, 'user_agent' => $ua])
            ->take(-self::TRUST_IP_MAX)
            ->values()
            ->all();

        $this->attachMetadata('2fa_trusted_ips', json_encode($entries));
    }

    /**
     * Hard cap on guesses against a single email code. The code lives 5 minutes
     * in a 900k pool; without this cap an attacker can keep guessing within the
     * window and gain a non-trivial success probability.
     */
    public const EMAIL_2FA_MAX_ATTEMPTS = 5;

    /**
     * Soft cap on how many full attempt-cycles the user can burn before the
     * mailbox goes silent. With MAX_ATTEMPTS=5 and MAX_CYCLES=3, the total
     * attacker budget over a cooldown window is ~15 guesses against a 900k
     * pool: ~2.4e-5 chance of a hit per window.
     */
    public const EMAIL_2FA_MAX_CYCLES = 3;

    public const EMAIL_2FA_COOLDOWN_MINUTES = 5;

    public function sendTwoFactorEmailCode(string $guard, ?string $ip = null): void
    {
        if ($this->isEmailTwoFactorOnCooldown()) {
            return;
        }

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

        if (! $hash || ! $expiresAt) {
            return false;
        }

        if (now()->gt(\Carbon\Carbon::parse($expiresAt))) {
            $this->clearEmailTwoFactorCode();

            return false;
        }

        if (! Hash::check($code, $hash)) {
            $attempts = (int) ($this->getMetadata('2fa_email_code_attempts') ?: 0) + 1;
            if ($attempts >= self::EMAIL_2FA_MAX_ATTEMPTS) {
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
        if ($burned < self::EMAIL_2FA_MAX_CYCLES) {
            return false;
        }

        $burnedAt = $this->getMetadata('2fa_email_burned_at');
        if (! $burnedAt) {
            return false;
        }

        if (now()->gte(\Carbon\Carbon::parse($burnedAt)->addMinutes(self::EMAIL_2FA_COOLDOWN_MINUTES))) {
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

    // Send OTP via configured SMS gateway. Mirrors sendTwoFactorEmailCode.
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
