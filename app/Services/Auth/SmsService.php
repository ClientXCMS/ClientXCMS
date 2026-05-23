<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Auth;

use App\Contracts\Auth\SmsGatewayContract;
use App\Services\Auth\Sms\LogSmsGateway;
use App\Services\Auth\Sms\TwilioSmsGateway;

/**
 * v2.16 — Resolves the configured SMS gateway based on the
 * `mfa_sms_driver` setting. Extensions can register additional
 * drivers via {@see SmsService::extend()}; the resolution falls back
 * to {@see LogSmsGateway} when the operator has not configured
 * anything — which keeps a fresh install from blowing up.
 */
class SmsService
{
    /** @var array<string, callable(): SmsGatewayContract> */
    private static array $factories = [];

    public static function extend(string $driver, callable $factory): void
    {
        self::$factories[strtolower($driver)] = $factory;
    }

    public static function gateway(): SmsGatewayContract
    {
        $driver = strtolower((string) setting('mfa_sms_driver', 'log'));

        if (isset(self::$factories[$driver])) {
            return (self::$factories[$driver])();
        }

        return match ($driver) {
            'twilio' => new TwilioSmsGateway,
            'log', '' => new LogSmsGateway,
            default => new LogSmsGateway, // unknown driver — degrade safely
        };
    }

    /**
     * Returns the list of driver keys the admin UI can offer in the
     * MFA settings dropdown.
     *
     * @return array<string, string> [driver_key => human_label]
     */
    public static function availableDrivers(): array
    {
        $base = [
            'log' => 'Log only (development)',
            'twilio' => 'Twilio',
        ];
        foreach (array_keys(self::$factories) as $key) {
            $base[$key] = ucfirst($key);
        }

        return $base;
    }
}
