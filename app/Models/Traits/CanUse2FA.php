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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FAQRCode\Google2FA;

trait CanUse2FA
{
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
