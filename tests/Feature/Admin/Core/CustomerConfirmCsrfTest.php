<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerConfirmCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.customers.confirm');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods, 'confirm must be reachable via POST');
        $this->assertNotContains('GET', $methods, 'confirm must NOT be reachable via GET (CSRF surface)');
    }

    public function test_get_request_does_not_mark_email_verified(): void
    {
        $customer = Customer::factory()->unverified()->create();

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.confirm', $customer),
            [],
            ['admin.manage_customers']
        );

        $this->assertContains($response->status(), [404, 405], 'GET must not reach the controller');
        $this->assertNull(
            $customer->fresh()->email_verified_at,
            'GET must not mutate state - email_verified_at must remain null after a CSRF-style GET'
        );
    }

    public function test_post_marks_email_verified(): void
    {
        $customer = Customer::factory()->unverified()->create();

        $response = $this->performAdminAction(
            'POST',
            route('admin.customers.confirm', $customer),
            [],
            ['admin.manage_customers']
        );

        $this->assertNotSame(405, $response->status());
        $this->assertNotNull(
            $customer->fresh()->email_verified_at,
            'POST must perform the email confirmation'
        );
    }
}
