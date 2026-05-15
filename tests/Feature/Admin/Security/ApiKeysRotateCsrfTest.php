<?php

namespace Tests\Feature\Admin\Security;

use Tests\TestCase;

class ApiKeysRotateCsrfTest extends TestCase
{
    public function test_rotate_route_does_not_bypass_csrf(): void
    {
        $route = \Route::getRoutes()->getByName('admin.api-keys.rotate');
        $this->assertNotNull($route);
        $excluded = $route->excludedMiddleware();
        $bypass = collect($excluded)->contains(function ($m) {
            return $m === 'csrf'
                || $m === \App\Http\Middleware\VerifyCsrfToken::class
                || (is_string($m) && str_contains($m, 'CsrfToken'));
        });
        $this->assertFalse($bypass, 'rotate must keep CSRF middleware (no withoutMiddleware exemption)');
    }

    public function test_rotate_method_is_put_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.api-keys.rotate');
        $methods = $route->methods();
        $this->assertContains('PUT', $methods);
        $this->assertNotContains('GET', $methods, 'rotate must not accept GET (would be CSRF-able mutation)');
    }
}
