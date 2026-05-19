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
        $this->attachMetadata('2fa_recovery_codes', implode(',', $codes));
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
            $this->attachMetadata('2fa_recovery_codes', implode(',', $codes));
        }

        return explode(',', $this->getMetadata('2fa_recovery_codes'));
    }

    public function isValidRecoveryCode(string $code): bool
    {
        return collect($this->twoFactorRecoveryCodes())
            ->contains(fn ($recoveryCode) => $recoveryCode == $code);
    }

    public function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn () => $this->generateRecoveryCode())->all();
    }

    public function generateRecoveryCode(): string
    {
        return bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4));
    }

    public function useRecoveryCode(string $code): void
    {
        $recoveryCodes = $this->twoFactorRecoveryCodes();
        $recoveryCodes = collect($recoveryCodes)->filter(fn ($recoveryCode) => $recoveryCode != $code)->all();
        $this->attachMetadata('2fa_recovery_codes', implode(',', $recoveryCodes));
    }

    public function isValidate2FA(string $code): bool
    {
        $code = str_replace(' ', '', $code);
        if ($this->isValidEmailTwoFactorCode($code)) {
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

    public function twoFactorVerified(): bool
    {
        return Session::get('2fa_verified', false);
    }
}
