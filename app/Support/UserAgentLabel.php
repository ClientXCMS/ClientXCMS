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

namespace App\Support;

// UA -> "Firefox on Linux" for trusted-devices UI. Best-effort, no lib for
// a 20-row list. Detection order matters (Chrome/Edge advertise "Safari",
// Edge advertises "Chrome"); first match wins.
final class UserAgentLabel
{
    public static function summarize(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return __('client.profile.2fa.unknown_device');
        }

        $browser = self::browser($userAgent);
        $os = self::os($userAgent);

        if ($os === null) {
            return $browser;
        }

        return __('client.profile.2fa.device_label', ['browser' => $browser, 'os' => $os]);
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Edg/'), str_contains($ua, 'Edge/') => 'Edge',
            str_contains($ua, 'OPR/'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => __('client.profile.2fa.unknown_browser'),
        };
    }

    private static function os(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS X'), str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };
    }
}
