<?php

namespace Tests\Feature\Api\Client;

use Tests\TestCase;

class PasswordTokenRevokeTest extends TestCase
{
    public function test_password_change_method_revokes_other_tokens(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Api\Client\ProfileController::class, 'password');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            '->tokens()',
            $body,
            'profile password change must touch ->tokens() to revoke stale sessions'
        );
        $this->assertStringContainsString(
            '->delete()',
            $body,
            'profile password change must call ->delete() on the tokens query'
        );
    }

    public function test_password_reset_callback_revokes_all_tokens(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Api\Client\AuthController::class, 'resetPassword');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            '$user->tokens()->delete()',
            $body,
            'password reset must drop every existing token so a previously-leaked one cannot be replayed against the new password'
        );
    }
}
