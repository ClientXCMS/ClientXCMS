<?php

namespace Tests\Feature\Front;

use App\Models\Billing\Gateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayWebhookCsrfTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): Gateway
    {
        return Gateway::firstOrCreate(['uuid' => 'balance'], [
            'name' => 'Balance',
            'status' => 'active',
            'id' => 1,
        ]);
    }

    public function test_webhook_route_is_post_only(): void
    {
        $route = \Route::getRoutes()->getByName('gateways.notification');
        $this->assertNotNull($route);
        $methods = $route->methods();
        $this->assertContains('POST', $methods, 'Webhook must be reachable via POST');
        $this->assertNotContains('GET', $methods, 'Webhook must NOT accept GET (CSRF surface via <img src=...>)');
        $this->assertNotContains('PUT', $methods, 'Webhook must NOT accept PUT');
        $this->assertNotContains('DELETE', $methods, 'Webhook must NOT accept DELETE');
    }

    public function test_webhook_accepts_post_requests(): void
    {
        $gw = $this->gateway();

        $response = $this->post(route('gateways.notification', ['gateway' => $gw->uuid]));

        $this->assertNotSame(405, $response->status(), 'Webhook must remain reachable via POST');
    }
}
