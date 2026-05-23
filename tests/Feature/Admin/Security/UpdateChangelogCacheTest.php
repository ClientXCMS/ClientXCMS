<?php

namespace Tests\Feature\Admin\Security;

use Tests\TestCase;

class UpdateChangelogCacheTest extends TestCase
{
    public function test_changelog_uses_bounded_cache_not_remember_forever(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/Admin/Security/UpdateController.php'));
        $this->assertStringNotContainsString(
            "rememberForever('changelogs'",
            $src,
            'changelogs cache must have a TTL - rememberForever locks a poisoned/stale response permanently until manual cache:clear'
        );
        $this->assertStringContainsString(
            "remember('changelogs'",
            $src,
            'Use Cache::remember(key, ttl, ...) instead'
        );
    }
}
