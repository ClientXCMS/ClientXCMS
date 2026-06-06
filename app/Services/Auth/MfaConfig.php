<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Auth;

/**
 * Single source of truth for MFA tunables. Reads runtime settings
 * (DB-backed via setting()) first, falls back to config('mfa.*'). Use
 * this everywhere instead of hard-coding magic numbers in the trait.
 *
 * Settings precedence: setting(key) > config(path) > default.
 */
final class MfaConfig
{
    public static function emailMaxAttempts(): int
    {
        return (int) self::resolve('mfa_email_max_attempts', 'mfa.email.max_attempts', 5);
    }

    public static function emailMaxCycles(): int
    {
        return (int) self::resolve('mfa_email_max_cycles', 'mfa.email.max_cycles', 3);
    }

    public static function emailCooldownMinutes(): int
    {
        return (int) self::resolve('mfa_email_cooldown_minutes', 'mfa.email.cooldown_minutes', 5);
    }

    public static function emailCodeTtlMinutes(): int
    {
        return (int) self::resolve('mfa_email_code_ttl_minutes', 'mfa.email.code_ttl_minutes', 5);
    }

    public static function smsDailyCap(): int
    {
        return (int) self::resolve('mfa_sms_daily_cap', 'mfa.sms.daily_cap', 10);
    }

    public static function smsCodeTtlMinutes(): int
    {
        return (int) self::resolve('mfa_sms_code_ttl_minutes', 'mfa.sms.code_ttl_minutes', 5);
    }

    public static function smsDefaultDriver(): string
    {
        return (string) self::resolve('mfa_sms_driver', 'mfa.sms.default_driver', 'log');
    }

    public static function trustedDevicesMax(): int
    {
        return (int) self::resolve('mfa_trusted_devices_max', 'mfa.trusted_devices.max_entries', 20);
    }

    public static function trustedDeviceLifetimeDays(): int
    {
        return max(1, (int) self::resolve('trust_device_days', 'mfa.trusted_devices.default_lifetime_days', 30));
    }

    public static function forceFor(string $guard): bool
    {
        $key = $guard === 'admin' ? 'force_2fa_admin' : 'force_2fa_client';
        $configPath = $guard === 'admin' ? 'mfa.force.admin' : 'mfa.force.client';
        $raw = self::resolve($key, $configPath, false);

        return in_array($raw, ['true', true, 1, '1'], true);
    }

    /**
     * Resolve setting() then config() then default. setting() may
     * return null when the row is missing.
     */
    private static function resolve(string $settingKey, string $configPath, mixed $default): mixed
    {
        $fromSetting = setting($settingKey);
        if ($fromSetting !== null && $fromSetting !== '') {
            return $fromSetting;
        }

        return config($configPath, $default);
    }
}
