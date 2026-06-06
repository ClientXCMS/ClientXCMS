<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Auth;

use App\Contracts\Auth\SmsGatewayContract;
use App\Services\Auth\Sms\LogSmsGateway;

/**
 * Registry of SMS gateways. Only the in-tree `log` driver is hardcoded;
 * extensions add their own (twilio, ovh, vonage, ...) via extend().
 * Unknown driver -> falls back to log (defense in depth).
 */
class SmsService
{
    /** @var array<string, array{factory: callable(): SmsGatewayContract, label: string}> */
    private static array $factories = [];

    public static function extend(string $driver, callable $factory, ?string $label = null): void
    {
        $key = strtolower($driver);
        self::$factories[$key] = [
            'factory' => $factory,
            'label' => $label ?? ucfirst($key),
        ];
    }

    public static function forget(string $driver): void
    {
        unset(self::$factories[strtolower($driver)]);
    }

    public static function gateway(): SmsGatewayContract
    {
        $driver = strtolower((string) setting('mfa_sms_driver', 'log'));

        if (isset(self::$factories[$driver])) {
            return (self::$factories[$driver]['factory'])();
        }

        return new LogSmsGateway;
    }

    /**
     * @return array<string, string> [driver_key => human_label]
     */
    public static function availableDrivers(): array
    {
        $base = ['log' => 'Log only (development)'];
        foreach (self::$factories as $key => $entry) {
            $base[$key] = $entry['label'];
        }

        return $base;
    }
}
