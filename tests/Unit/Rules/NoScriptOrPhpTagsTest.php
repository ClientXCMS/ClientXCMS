<?php

namespace Tests\Unit\Rules;

use App\Rules\NoScriptOrPhpTags;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NoScriptOrPhpTagsTest extends TestCase
{
    public static function rejectedProvider(): array
    {
        return [
            // Original blocklist (backwards compat)
            'script tag lowercase' => ['<script>alert(1)</script>'],
            'script tag uppercase' => ['<SCRIPT>alert(1)</SCRIPT>'],
            'php open tag' => ['<?php phpinfo();'],
            'php short open' => ['<?= $secret ?>'],
            'blade interpolation' => ['hello {{ $secret }}'],
            // New X10 hardening
            'script with attribute' => ['<script src="//evil.com/x.js"></script>'],
            'svg payload' => ['<svg onload=alert(1)>'],
            'svg with script child' => ['<svg><script>alert(1)</script></svg>'],
            'iframe' => ['<iframe src="//evil.com"></iframe>'],
            'object data' => ['<object data="javascript:alert(1)"></object>'],
            'embed' => ['<embed src="data:text/html,<script>alert(1)</script>">'],
            'base hijack' => ['<base href="//evil.com/">'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=//evil.com">'],
            'link import' => ['<link rel="import" href="//evil.com/x.html">'],
            'style with expression' => ['<style>x{width: expression(alert(1))}</style>'],
            'javascript URI' => ['<a href="javascript:alert(1)">x</a>'],
            'vbscript URI' => ['<a href="vbscript:alert(1)">x</a>'],
            'data text/html URI' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>'],
            'inline onerror handler' => ['<img src=x onerror=alert(1)>'],
            'inline onload handler' => ['<body onload=alert(1)>'],
            'inline onclick handler' => ['<a onclick="alert(1)">x</a>'],
            'iframe srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>'],
        ];
    }

    public static function acceptedProvider(): array
    {
        return [
            'plain text' => ['Hello, this is my address: 123 Main St'],
            'basic formatting tags' => ['<p><strong>Welcome</strong> to <em>our</em> store</p>'],
            'simple link' => ['<a href="https://example.com/help">Help center</a>'],
            'list' => ['<ul><li>One</li><li>Two</li></ul>'],
            'image with safe src' => ['<img src="https://cdn.example.com/logo.png" alt="logo">'],
            'multiline text with newlines' => ["Line 1\nLine 2\nLine 3"],
            'unicode' => ['Café Résumé 中文'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function test_rejects(string $payload): void
    {
        $this->assertFalse((new NoScriptOrPhpTags)->passes('content', $payload));
    }

    #[DataProvider('acceptedProvider')]
    public function test_accepts(string $payload): void
    {
        $this->assertTrue(
            (new NoScriptOrPhpTags)->passes('content', $payload),
            "Expected to accept legitimate input: {$payload}"
        );
    }
}
