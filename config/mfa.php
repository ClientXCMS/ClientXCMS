<?php

return [
    /*
     * One-time email codes (channel = 'email').
     */
    'email' => [
        'max_attempts' => 5,          // hard cap on guesses per code
        'max_cycles' => 3,            // burned cycles before mailbox cooldown
        'cooldown_minutes' => 5,      // mailbox silenced this long after burning the cycles
        'code_ttl_minutes' => 5,      // OTP validity window
    ],

    /*
     * One-time SMS codes (channel = 'sms').
     */
    'sms' => [
        'daily_cap' => 10,            // max SMS per user per rolling 24h, anti cost-attack
        'code_ttl_minutes' => 5,      // OTP validity window
        'default_driver' => 'log',    // setting('mfa_sms_driver') overrides at runtime
    ],

    /*
     * Trusted-device list (skip 2FA on known IPs).
     */
    'trusted_devices' => [
        'max_entries' => 20,                              // soft cap, oldest evicted first
        'default_lifetime_days' => 30,                    // setting('trust_device_days') overrides
    ],

    /*
     * Forcing 2FA per guard (admin/client). Mirrors the existing
     * `force_2fa_admin` / `force_2fa_client` settings.
     */
    'force' => [
        'admin' => false,
        'client' => false,
    ],
];
