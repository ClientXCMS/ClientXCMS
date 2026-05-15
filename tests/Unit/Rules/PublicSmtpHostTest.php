<?php

namespace Tests\Unit\Rules;

use App\Rules\PublicSmtpHost;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PublicSmtpHostTest extends TestCase
{
    public static function rejectedProvider(): array
    {
        return [
            'AWS metadata IPv4' => ['169.254.169.254'],
            'IPv4 loopback' => ['127.0.0.1'],
            'IPv4 RFC1918 10/8' => ['10.0.0.5'],
            'IPv4 RFC1918 192.168/16' => ['192.168.1.1'],
            'IPv4 RFC1918 172.16/12' => ['172.16.0.1'],
            'IPv6 loopback' => ['::1'],
            'IPv4 0.0.0.0' => ['0.0.0.0'],
            'localhost name' => ['localhost'],
            'empty' => [''],
            'whitespace only' => ['   '],
        ];
    }

    public static function acceptedProvider(): array
    {
        return [
            'public hostname' => ['smtp.sendgrid.net'],
            'gmail smtp' => ['smtp.gmail.com'],
            'public IPv4 (Google DNS)' => ['8.8.8.8'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function test_rejects_non_public_host(string $host): void
    {
        $this->assertFalse((new PublicSmtpHost)->passes('mail_smtp_host', $host), "Must reject {$host}");
    }

    #[DataProvider('acceptedProvider')]
    public function test_accepts_public_host(string $host): void
    {
        $this->assertTrue((new PublicSmtpHost)->passes('mail_smtp_host', $host), "Must accept {$host}");
    }
}
