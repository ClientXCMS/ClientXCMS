<?php

namespace Tests\Unit\Support;

use App\Support\UserAgentLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAgentLabelTest extends TestCase
{
    public static function knownAgentsProvider(): array
    {
        return [
            'Firefox on Linux' => [
                'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
                'Firefox',
                'Linux',
            ],
            'Chrome on Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
                'Chrome',
                'Windows',
            ],
            'Edge wins over Chrome marker' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127 Safari/537.36 Edg/127.0',
                'Edge',
                'Windows',
            ],
            'Safari on macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
                'Safari',
                'macOS',
            ],
            'Chrome on Android' => [
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/127 Mobile Safari/537.36',
                'Chrome',
                'Android',
            ],
            'Safari on iOS iPhone' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1',
                'Safari',
                'iOS',
            ],
        ];
    }

    #[DataProvider('knownAgentsProvider')]
    public function test_summarizes_known_browsers_and_oses(string $ua, string $browser, string $os): void
    {
        $label = UserAgentLabel::summarize($ua);

        $this->assertStringContainsString($browser, $label);
        $this->assertStringContainsString($os, $label);
    }

    public function test_returns_unknown_device_label_for_null_or_empty(): void
    {
        $this->assertSame(__('client.profile.2fa.unknown_device'), UserAgentLabel::summarize(null));
        $this->assertSame(__('client.profile.2fa.unknown_device'), UserAgentLabel::summarize(''));
        $this->assertSame(__('client.profile.2fa.unknown_device'), UserAgentLabel::summarize('   '));
    }

    public function test_falls_back_to_unknown_browser_when_ua_is_unparseable(): void
    {
        $label = UserAgentLabel::summarize('some-curl-script/1.0');

        $this->assertStringContainsString(__('client.profile.2fa.unknown_browser'), $label);
    }
}
