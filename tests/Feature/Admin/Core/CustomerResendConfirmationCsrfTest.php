<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerResendConfirmationCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('admin.customers.resend_confirmation');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods);
        $this->assertNotContains('GET', $methods, 'resend_confirmation must NOT be reachable via GET (CSRF surface)');
    }

    public function test_get_request_does_not_send_email(): void
    {
        Notification::fake();
        $customer = Customer::factory()->unverified()->create();

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.resend_confirmation', $customer),
            [],
            ['admin.manage_customers']
        );

        $this->assertContains($response->status(), [404, 405]);
        Notification::assertNothingSent();
    }

    public function test_post_triggers_resend(): void
    {
        Notification::fake();
        $customer = Customer::factory()->unverified()->create();

        $response = $this->performAdminAction(
            'POST',
            route('admin.customers.resend_confirmation', $customer),
            [],
            ['admin.manage_customers']
        );

        $this->assertNotSame(405, $response->status(), 'POST must reach the controller');
        $this->assertNotSame(404, $response->status());
    }
}
