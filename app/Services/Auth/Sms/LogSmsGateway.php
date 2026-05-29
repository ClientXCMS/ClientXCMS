<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Auth\Sms;

use App\Contracts\Auth\SmsGatewayContract;

/**
 * v2.16 — Default SMS gateway: writes the would-be SMS into the
 * application log. Useful in development and as a fail-safe when no
 * real provider has been configured by the operator.
 *
 * The OTP itself is never logged — only the recipient and a redacted
 * placeholder. Anyone tailing the log can confirm the code was emitted
 * and read the actual digits from the metadata stored on the user
 * (which is itself bcrypt-hashed before persistence).
 */
class LogSmsGateway implements SmsGatewayContract
{
    public function send(string $to, string $message): void
    {
        logger()->info('mfa.sms.log_driver', [
            'to' => $this->mask($to),
            // Intentional: do NOT log the OTP. Operators using the log
            // driver in production are taking it on themselves.
            'body_chars' => strlen($message),
        ]);
    }

    public function name(): string
    {
        return 'log';
    }

    private function mask(string $to): string
    {
        $len = strlen($to);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($to, 0, 2).str_repeat('*', max(0, $len - 4)).substr($to, -2);
    }
}
