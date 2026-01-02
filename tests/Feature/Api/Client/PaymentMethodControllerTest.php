<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private function authenticatedCustomer(): array
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['*']);

        return [$customer, $token->plainTextToken];
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_customer_can_list_payment_methods(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/payment-methods');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'default_payment_method',
            ]);
    }

    public function test_customer_can_list_available_gateways(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createGatewayModel();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/payment-methods/gateways');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_payment_methods(): void
    {
        $response = $this->getJson('/api/client/payment-methods');

        $response->assertUnauthorized();
    }
}
