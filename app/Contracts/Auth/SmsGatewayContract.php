<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Contracts\Auth;

/**
 * v2.16 — Pluggable SMS gateway used by the MFA SMS driver and any
 * future "send a code by SMS" flow. Implementations are resolved by
 * {@see \App\Services\Auth\SmsService} based on the `mfa_sms_driver`
 * setting.
 *
 * Implementations must:
 *   - throw on transport errors (the caller logs + falls back to email)
 *   - normalise the destination number to E.164 if their API requires it
 *   - never log the message body (it contains a one-time code)
 */
interface SmsGatewayContract
{
    public function send(string $to, string $message): void;

    public function name(): string;
}
