<?php

namespace Tests\Feature\Admin\Core;

use Tests\TestCase;

class CustomerImpersonationLogoutCsrfTest extends TestCase
{
    public function test_logout_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.customers.logout');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods);
        $this->assertNotContains('GET', $methods, 'end-impersonation must NOT be reachable via GET (CSRF surface)');
    }
}
