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

    public static function make(string $driver): SmsGatewayContract
    {
        $driver = strtolower($driver);

        if (isset(self::$factories[$driver])) {
            return (self::$factories[$driver]['factory'])();
        }

        return new LogSmsGateway;
    }

    public static function gateway(): SmsGatewayContract
    {
        $driver = strtolower((string) setting('mfa_sms_driver', 'log'));

        return self::make($driver);
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
