<?php

namespace Tests\Feature;

use Tests\TestCase;

class SanctumTokenExpirationTest extends TestCase
{
    public function test_sanctum_expiration_is_bounded(): void
    {
        $expiration = config('sanctum.expiration');
        $this->assertNotNull(
            $expiration,
            'sanctum.expiration must be set: null means tokens live forever and a leaked token can be replayed indefinitely'
        );
        $this->assertGreaterThan(0, $expiration);
        $this->assertLessThanOrEqual(
            60 * 24 * 90,
            $expiration,
            'sanctum.expiration should not exceed 90 days; rotate sooner via SANCTUM_TOKEN_EXPIRATION env var'
        );
    }

    public function test_sanctum_expiration_can_be_overridden_via_env(): void
    {
        config(['sanctum.expiration' => null]);
        putenv('SANCTUM_TOKEN_EXPIRATION=1440');

        $src = file_get_contents(config_path('sanctum.php'));
        $this->assertStringContainsString(
            "env('SANCTUM_TOKEN_EXPIRATION'",
            $src,
            'Operators must be able to tighten or loosen the TTL via env without editing config'
        );
    }
}
