<?php

namespace Tests\Feature\Front\Store;

use Tests\TestCase;

class StoreRedirectOpenRedirectTest extends TestCase
{
    public function test_store_disabled_redirect_uses_secure_redirect(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Front\Store\StoreController::class, '__construct');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'secure_redirect(',
            $body,
            'StoreController must route the store_redirect_url setting through secure_redirect() to prevent an admin-supplied open redirect on every store page'
        );
        $this->assertStringNotContainsString(
            'return redirect($url',
            $body,
            'Raw redirect($var) is the open-redirect anti-pattern'
        );
    }
}
