<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Pins the session-rotation invariant: 2FA verification must call
 * session()->regenerate() before flagging 2fa_verified, otherwise a
 * fixed SID can be promoted to 2fa-verified state by the victim.
 *
 * Originally scoped to the verify() method body but the F1 refactor
 * extracted the rotation into a completeFlow() helper - the invariant
 * still holds across the whole controller, so we widen the search.
 */
class TwoFactorSessionRegenerateTest extends TestCase
{
    public function test_customer_2fa_controller_calls_session_regenerate(): void
    {
        $this->assertControllerRotatesSession(
            \App\Http\Controllers\Auth\TwoFactorAuthenticationController::class
        );
    }

    public function test_admin_2fa_controller_calls_session_regenerate(): void
    {
        $this->assertControllerRotatesSession(
            \App\Http\Controllers\Admin\Auth\TwoFactorAuthenticationController::class
        );
    }

    private function assertControllerRotatesSession(string $class): void
    {
        $reflection = new \ReflectionClass($class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString(
            'session()->regenerate()',
            $source,
            "{$class} must rotate the session ID before flagging 2fa_verified - otherwise a fixed SID can be promoted to 2fa-verified by the victim"
        );
    }
}
