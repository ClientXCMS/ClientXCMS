<?php

namespace Tests\Feature\Api\Client;

use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    public static function authRouteProvider(): array
    {
        return [
            ['api.client.auth.login'],
            ['api.client.auth.register'],
            ['api.client.auth.forgot-password'],
            ['api.client.auth.reset-password'],
        ];
    }

    /**
     * @dataProvider authRouteProvider
     */
    public function test_auth_route_has_dedicated_throttle_middleware(string $name): void
    {
        $route = \Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "route {$name} must exist");
        $middleware = collect($route->gatherMiddleware());
        $hasTighterThrottle = $middleware->contains(function ($m) {
            if (! is_string($m) || ! str_starts_with($m, 'throttle:')) {
                return false;
            }
            $hits = (int) explode(',', substr($m, strlen('throttle:')))[0];

            return $hits > 0 && $hits <= 30; // tighter than the default 60/min api throttle
        });
        $this->assertTrue(
            $hasTighterThrottle,
            "auth route {$name} must declare a throttle stricter than the default api throttle (60/min) to slow brute-force / enumeration"
        );
    }
}
