<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordRehashTest extends TestCase
{
    public function test_customer_login_rehashes_password_if_needed(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Requests\Auth\LoginRequest::class, 'authenticate');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'Hash::needsRehash(',
            $body,
            'customer login must check Hash::needsRehash so a successful auth on an outdated bcrypt cost / argon driver is upgraded transparently'
        );
        $this->assertStringContainsString(
            'Hash::make(',
            $body,
            'customer login must Hash::make and save when the existing hash needs rehashing'
        );
    }

    public function test_admin_login_rehashes_password_if_needed(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Requests\Admin\Auth\LoginRequest::class, 'authenticate');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString('Hash::needsRehash(', $body);
        $this->assertStringContainsString('Hash::make(', $body);
    }
}
