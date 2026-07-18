<?php

namespace Tests\Feature\Core\License;

use App\Core\License\LicenseGateway;
use Tests\TestCase;

class LicenseDomainAllowlistTest extends TestCase
{
    private function withCtxDomain(?string $value, callable $fn): mixed
    {
        $previous = $_ENV['CTX_DOMAIN'] ?? null;
        if ($value === null) {
            unset($_ENV['CTX_DOMAIN'], $_SERVER['CTX_DOMAIN']);
            putenv('CTX_DOMAIN');
        } else {
            $_ENV['CTX_DOMAIN'] = $value;
            $_SERVER['CTX_DOMAIN'] = $value;
            putenv('CTX_DOMAIN='.$value);
        }
        try {
            return $fn();
        } finally {
            if ($previous === null) {
                unset($_ENV['CTX_DOMAIN'], $_SERVER['CTX_DOMAIN']);
                putenv('CTX_DOMAIN');
            } else {
                $_ENV['CTX_DOMAIN'] = $previous;
                $_SERVER['CTX_DOMAIN'] = $previous;
                putenv('CTX_DOMAIN='.$previous);
            }
        }
    }

    public function test_attacker_domain_is_rejected_and_falls_back_to_canonical(): void
    {
        $this->withCtxDomain('https://attacker.example/', function () {
            $this->assertSame('https://clientxcms.com', LicenseGateway::getDomain());
        });
    }

    public function test_http_scheme_is_rejected(): void
    {
        $this->withCtxDomain('http://clientxcms.com', function () {
            $this->assertSame('https://clientxcms.com', LicenseGateway::getDomain());
        });
    }

    public function test_legit_subdomain_is_accepted(): void
    {
        $this->withCtxDomain('https://staging.clientxcms.com', function () {
            $this->assertSame('https://staging.clientxcms.com', LicenseGateway::getDomain());
        });
    }

    public function test_default_when_env_missing(): void
    {
        $this->withCtxDomain(null, function () {
            $this->assertSame('https://clientxcms.com', LicenseGateway::getDomain());
        });
    }

    public function test_lookalike_typosquat_is_rejected(): void
    {
        $this->withCtxDomain('https://clientxcms.com.attacker.example', function () {
            $this->assertSame('https://clientxcms.com', LicenseGateway::getDomain());
        });
    }
}
