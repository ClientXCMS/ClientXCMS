<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Auth\Sms;

use App\Contracts\Auth\SmsGatewayContract;

/**
 * v2.16 — Twilio implementation of {@see SmsGatewayContract}. Uses
 * the bare REST API over Guzzle so we don't have to depend on the
 * Twilio SDK (composer.json already has guzzlehttp/guzzle).
 *
 * Credentials are read from operator-level settings, not env vars,
 * so multi-tenant installs can override per instance:
 *   - mfa_sms_twilio_sid
 *   - mfa_sms_twilio_token
 *   - mfa_sms_twilio_from   (E.164 sender)
 */
class TwilioSmsGateway implements SmsGatewayContract
{
    public function send(string $to, string $message): void
    {
        $sid = (string) setting('mfa_sms_twilio_sid');
        $token = (string) setting('mfa_sms_twilio_token');
        $from = (string) setting('mfa_sms_twilio_from');

        if ($sid === '' || $token === '' || $from === '') {
            throw new \RuntimeException('Twilio SMS gateway is missing credentials.');
        }

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', urlencode($sid));
        $response = \Http::asForm()
            ->withBasicAuth($sid, $token)
            ->timeout(8)
            ->post($url, [
                'To' => $to,
                'From' => $from,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Twilio SMS send failed: HTTP '.$response->status());
        }
    }

    public function name(): string
    {
        return 'twilio';
    }
}
