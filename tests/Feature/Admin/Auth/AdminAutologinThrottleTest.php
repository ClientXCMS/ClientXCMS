<?php

namespace Tests\Feature\Admin\Auth;

use Tests\TestCase;

class AdminAutologinThrottleTest extends TestCase
{
    public function test_autologin_route_has_throttle_middleware(): void
    {
        $route = \Route::getRoutes()->getByName('admin.autologin');
        $this->assertNotNull($route);
        $middleware = collect($route->gatherMiddleware());
        $hasThrottle = $middleware->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));
        $this->assertTrue(
            $hasThrottle,
            'admin.autologin must declare a throttle:N,M middleware to mitigate DoS via repeated replay attempts'
        );
    }
}
