<?php

namespace Tests\Feature\Admin\Core;

use Tests\TestCase;

class AdminPasswordChangeTokenRevokeTest extends TestCase
{
    public function test_update_password_rotates_remember_token_and_drops_api_tokens(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Core\AdminController::class, 'updatePassword');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            "'remember_token' => \\Str::random(60)",
            $body,
            'updatePassword must rotate remember_token so a leaked one cannot survive a password change'
        );
        $this->assertStringContainsString(
            'logoutOtherDevices(',
            $body,
            'updatePassword must Auth::logoutOtherDevices so concurrent admin web sessions are invalidated'
        );
        $this->assertStringContainsString(
            '->tokens()->delete()',
            $body,
            'updatePassword must wipe Sanctum API tokens for the admin'
        );
    }
}
