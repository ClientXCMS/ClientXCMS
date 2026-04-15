<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
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
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    public function test_customer_can_list_services(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createServiceModel($customer->id, 'active');
        $this->createServiceModel($customer->id, 'suspended');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'status', 'status_label', 'expires_at', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_filter_services_by_status(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createServiceModel($customer->id, 'active');
        $this->createServiceModel($customer->id, 'suspended');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_see_other_customer_services(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $this->createServiceModel($otherCustomer->id, 'active');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_can_view_service_details(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $service = $this->createServiceModel($customer->id, 'active');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services/'.$service->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'status',
                    'status_label',
                    'billing_cycle',
                    'price_ht',
                    'price_ttc',
                    'expires_at',
                    'created_at',
                    'can_renew',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $service->id,
                    'name' => 'Test Service',
                ],
            ]);
    }

    public function test_customer_cannot_view_other_customer_service(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $service = $this->createServiceModel($otherCustomer->id, 'active');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services/'.$service->id);

        $response->assertNotFound();
    }

    public function test_service_details_include_suspension_info_when_suspended(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $service = $this->createServiceModel($customer->id, 'suspended');
        $service->suspend_reason = 'Non-payment';
        $service->suspended_at = now();
        $service->save();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/services/'.$service->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'suspension' => ['reason', 'suspended_at'],
                ],
            ]);
    }
}
