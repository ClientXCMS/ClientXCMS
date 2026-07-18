<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAutologinCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_autologin_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.customers.autologin');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods);
        $this->assertNotContains('GET', $methods, 'autologin must NOT be reachable via GET (CSRF-able impersonation)');
    }

    public function test_get_request_does_not_impersonate(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.autologin', $customer),
            [],
            ['admin.autologin_customer', 'admin.show_customers']
        );

        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_post_triggers_impersonation_redirect(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->performAdminAction(
            'POST',
            route('admin.customers.autologin', $customer),
            [],
            ['admin.autologin_customer', 'admin.show_customers']
        );

        $this->assertNotSame(405, $response->status(), 'POST must reach the controller');
        $this->assertNotSame(404, $response->status());
    }
}
