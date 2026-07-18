<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Validates that a hostname or IP literal is public-routable. Rejects
 * private (RFC1918 / RFC4193), loopback, link-local, and reserved
 * ranges. Used on operator-supplied SMTP / outbound destinations to
 * stop a hostile admin from pointing the mail queue at the host's
 * loopback (Redis, MySQL, ...) or at the cloud metadata service.
 *
 * Best-effort: not immune to DNS rebinding between settings save and
 * the actual SMTP connection. The mail driver itself should ideally
 * be locked down at the network layer in addition to this check.
 */
class PublicSmtpHost implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }
        $host = trim($value);
        // Allow mailer driver shortcuts that are not network hosts.
        if ($host === 'localhost') {
            return false;
        }
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return PublicHttpUrl::isPublicIp($host);
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
            if (! PublicHttpUrl::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'The :attribute must resolve to a public host (private, loopback and link-local addresses are not allowed).';
    }
}
