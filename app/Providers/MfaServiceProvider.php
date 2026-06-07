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
