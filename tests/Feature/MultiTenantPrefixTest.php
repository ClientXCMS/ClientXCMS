<?php

namespace Tests\Feature;

use Tests\TestCase;

class MultiTenantPrefixTest extends TestCase
{
    public function test_cache_prefix_includes_app_key_hash(): void
    {
        $src = file_get_contents(config_path('cache.php'));
        $this->assertStringContainsString(
            "hash('sha256', (string) env('APP_KEY'",
            $src,
            'cache.prefix must mix in a hash of APP_KEY so two tenants pointed at the same Redis server (typical SaaS deploy) cannot collide on cache keys even if APP_NAME is identical'
        );
    }

    public function test_session_cookie_name_includes_app_key_hash(): void
    {
        $src = file_get_contents(config_path('session.php'));
        $this->assertStringContainsString(
            "hash('sha256', (string) env('APP_KEY'",
            $src,
            'session.cookie must mix in a hash of APP_KEY so two tenants on a shared parent domain cannot read each other session cookies'
        );
    }
}
