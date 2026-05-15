<?php

namespace Tests\Feature\Admin\Core;

use Tests\TestCase;

class CustomerAutologinSessionTest extends TestCase
{
    private function autologinMethodSource(): string
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Core\CustomerController::class, 'autologin');
        $file = file($reflection->getFileName());

        return implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }

    private function logoutMethodSource(): string
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Core\CustomerController::class, 'logout');
        $file = file($reflection->getFileName());

        return implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }

    public function test_autologin_regenerates_session(): void
    {
        $this->assertStringContainsString(
            'Session::regenerate()',
            $this->autologinMethodSource(),
            'autologin() must call Session::regenerate() to prevent session fixation when impersonating a customer'
        );
    }

    public function test_autologin_forgets_two_factor_flags(): void
    {
        $this->assertStringContainsString(
            "Session::forget(['2fa_verified', '2fa_secret'])",
            $this->autologinMethodSource(),
            'autologin() must drop stale 2fa_verified/2fa_secret flags so they do not bleed into the impersonated session'
        );
    }

    public function test_autologin_logout_regenerates_session(): void
    {
        $this->assertStringContainsString(
            'Session::regenerate()',
            $this->logoutMethodSource(),
            'logout() must call Session::regenerate() when ending impersonation'
        );
    }

    public function test_autologin_logout_forgets_two_factor_flags(): void
    {
        $this->assertStringContainsString(
            "Session::forget(['2fa_verified', '2fa_secret'])",
            $this->logoutMethodSource(),
            'logout() must drop stale 2fa_verified/2fa_secret flags before returning to the admin context'
        );
    }
}
