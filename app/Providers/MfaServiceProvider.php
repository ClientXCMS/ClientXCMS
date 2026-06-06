<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Providers;

use App\Services\Auth\Sms\TwilioSmsGateway;
use App\Services\Auth\SmsService;
use Illuminate\Support\ServiceProvider;

/**
 * Registers built-in SMS gateways and merges config/mfa.php so
 * config('mfa.*') works out of the box.
 *
 * Twilio is registered here for backwards compatibility; the planned
 * extraction to a dedicated free addon can simply forget()+extend()
 * its own provider to override or replace.
 */
class MfaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/mfa.php', 'mfa');
    }

    public function boot(): void
    {
        SmsService::extend(
            'twilio',
            fn () => new TwilioSmsGateway,
            'Twilio',
        );
    }
}
