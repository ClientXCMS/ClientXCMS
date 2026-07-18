<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Validates that a value is an http(s) URL whose host resolves only to
 * public, routable IP addresses. Rejects:
 *   - any non-http(s) scheme (file://, gopher://, ftp://, ...)
 *   - IP literals in private / loopback / link-local / reserved ranges
 *   - hostnames that resolve (any A/AAAA record) to a private IP
 *
 * Used to block SSRF on operator-supplied webhook URLs (AWS IMDS at
 * 169.254.169.254, localhost services, internal network probes).
 *
 * Best-effort: not immune to DNS rebinding between validation time
 * and actual fetch. Pair with a runtime check at the request site.
 */
class PublicHttpUrl implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }
        $parts = parse_url($value);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        $host = trim($parts['host'], '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        $ips = gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            $resolved = gethostbyname($host);
            if ($resolved === $host) {
                return false;
            }
            $ips = [$resolved];
        }
        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public static function isPublicIp(string $ip): bool
    {
        if ($ip === '0.0.0.0' || $ip === '::') {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    public function message(): string
    {
        return 'The :attribute must be a public http(s) URL (private, loopback and link-local addresses are not allowed).';
    }
}
