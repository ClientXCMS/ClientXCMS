<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpsEnforcementTest extends TestCase
{
    public function test_app_service_provider_forces_https_when_flag_set(): void
    {
        $src = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString(
            'URL::forceScheme(',
            $src,
            'AppServiceProvider must call URL::forceScheme so generated URLs (signed routes, redirects, asset() helper) emit https in production'
        );
        $this->assertStringContainsString(
            "env('APP_FORCE_HTTPS'",
            $src,
            'The toggle must be env-controlled so dev / CI on plain HTTP can opt out'
        );
        $this->assertStringContainsString(
            "environment('production')",
            $src,
            "Default must be tied to environment('production') so 'production' env auto-enables HTTPS even without the env var"
        );
    }
}
