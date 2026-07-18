<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionEncryptionTest extends TestCase
{
    public function test_session_encryption_is_enabled_by_default(): void
    {
        $this->assertTrue(
            config('session.encrypt'),
            'session.encrypt must default to true so on-disk session payloads cannot be replayed by anyone with read access to storage/framework/sessions/'
        );
    }

    public function test_session_encryption_is_overridable_via_env(): void
    {
        $src = file_get_contents(config_path('session.php'));
        $this->assertStringContainsString(
            "env('SESSION_ENCRYPT'",
            $src,
            'Operators must be able to disable encryption via env if their session driver requires it'
        );
    }
}
