<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class TwoFactorSessionRegenerateTest extends TestCase
{
    public function test_customer_2fa_verify_calls_session_regenerate(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Auth\TwoFactorAuthenticationController::class, 'verify');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'session()->regenerate()',
            $body,
            'customer 2FA verify must rotate the session ID right before flagging 2fa_verified - otherwise a fixed SID can be promoted to 2fa-verified by the victim'
        );
    }

    public function test_admin_2fa_verify_calls_session_regenerate(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Auth\TwoFactorAuthenticationController::class, 'verify');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'session()->regenerate()',
            $body,
            'admin 2FA verify must rotate the session ID before flagging 2fa_verified'
        );
    }
}
