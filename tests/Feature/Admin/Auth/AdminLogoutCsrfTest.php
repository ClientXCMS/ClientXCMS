<?php

namespace Tests\Feature\Admin\Auth;

use Tests\TestCase;

class AdminLogoutCsrfTest extends TestCase
{
    public function test_admin_logout_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.logout');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods, 'Admin logout must be reachable via POST');
        $this->assertNotContains('GET', $methods, 'Admin logout must NOT accept GET (CSRF surface: <img src="/admin/logout"> would silently log out the admin)');
        $this->assertNotContains('PUT', $methods);
        $this->assertNotContains('DELETE', $methods);
    }
}
