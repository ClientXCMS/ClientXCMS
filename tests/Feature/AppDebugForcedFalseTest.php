<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppDebugForcedFalseTest extends TestCase
{
    public function test_production_environment_forces_debug_off(): void
    {
        $src = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString(
            "environment('production')",
            $src,
            'AppServiceProvider must check the production environment'
        );
        $this->assertMatchesRegularExpression(
            "/environment\\('production'\\)\\s*&&\\s*config\\('app.debug'\\)/",
            $src,
            'production && app.debug must be the gate that forces debug off, so an operator-leaked APP_DEBUG=true cannot ship Ignition stack traces in a SaaS deployment'
        );
        $this->assertStringContainsString(
            "config(['app.debug' => false])",
            $src,
            'app.debug must be flipped to false, not just warned about'
        );
    }
}
