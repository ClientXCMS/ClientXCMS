<?php

namespace Tests\Unit\Rules;

use App\Rules\PublicHttpUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PublicHttpUrlTest extends TestCase
{
    public static function rejectedProvider(): array
    {
        return [
            'AWS metadata service' => ['http://169.254.169.254/latest/meta-data/iam/security-credentials/'],
            'IPv4 loopback literal' => ['http://127.0.0.1/admin'],
            'IPv4 RFC1918 10/8' => ['https://10.0.0.5/'],
            'IPv4 RFC1918 192.168/16' => ['http://192.168.1.1/'],
            'IPv4 RFC1918 172.16/12' => ['http://172.16.0.1/'],
            'IPv6 loopback' => ['http://[::1]/'],
            'IPv4 0.0.0.0' => ['http://0.0.0.0/'],
            'gopher scheme' => ['gopher://internal/payload'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/'],
            'no scheme' => ['internal-service'],
            'localhost name resolving to loopback' => ['http://localhost/'],
            'empty string' => [''],
        ];
    }

    public static function acceptedProvider(): array
    {
        return [
            'HTTPS public domain' => ['https://discord.com/api/webhooks/123/abc'],
            'HTTP public domain' => ['http://example.com/webhook'],
            'HTTPS with path and query' => ['https://api.partner.io/v2/hook?x=1'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function test_rejects_non_public_url(string $url): void
    {
        $this->assertFalse((new PublicHttpUrl)->passes('webhook_url', $url), "Must reject {$url}");
    }

    #[DataProvider('acceptedProvider')]
    public function test_accepts_public_url(string $url): void
    {
        $this->assertTrue((new PublicHttpUrl)->passes('webhook_url', $url), "Must accept {$url}");
    }
}
