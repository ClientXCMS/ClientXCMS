<?php

namespace Tests\Feature\Api\Client;

use App\Abstracts\PaymentMethodSourceDTO;
use App\Contracts\Store\GatewayTypeInterface;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Services\Core\PaymentTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
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
            'Authorization' => 'Bearer '.$token,
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

    private function failingGateway(\Throwable $error, int $customerId = 0): Gateway
    {
        $type = \Mockery::mock(GatewayTypeInterface::class);
        $type->shouldReceive('sourceForm')->andReturn('<form></form>');
        $type->shouldReceive('addSource')->andThrow($error);
        $type->shouldReceive('removeSource')->andThrow($error);
        $type->shouldReceive('getSources')->andReturn([new PaymentMethodSourceDTO('src_test', 'visa', '4242', '12', '2030', $customerId, 'failing')]);
        $this->app->instance('tests.failing-gateway', $type);
        app(PaymentTypeService::class)->add('failing', 'tests.failing-gateway');

        return Gateway::create(['name' => 'Failing', 'uuid' => 'failing', 'status' => 'active']);
    }

    public function test_add_error_returns_a_generic_message_and_is_reported(): void
    {
        Exceptions::fake();
        [$customer, $token] = $this->authenticatedCustomer();
        $gateway = $this->failingGateway(new \RuntimeException('internal-provider-detail'));

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/payment-methods/'.$gateway->id);

        $response->assertStatus(400)->assertJsonPath('error', __('client.payment-methods.errors.generic'));
        $this->assertStringNotContainsString('internal-provider-detail', $response->getContent());
        Exceptions::assertReported(fn (\RuntimeException $e) => $e->getMessage() === 'internal-provider-detail');
    }

    public function test_add_validation_error_stays_a_422(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $gateway = $this->failingGateway(ValidationException::withMessages(['card' => 'invalid card']));

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/payment-methods/'.$gateway->id);

        $response->assertStatus(422)->assertJsonValidationErrors('card');
    }

    public function test_delete_error_returns_a_generic_message_and_is_reported(): void
    {
        Exceptions::fake();
        [$customer, $token] = $this->authenticatedCustomer();
        $this->failingGateway(new \RuntimeException('internal-provider-detail'), $customer->id);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson('/api/client/payment-methods/src_test');

        $response->assertStatus(400)->assertJsonPath('error', __('client.payment-methods.errors.generic'));
        $this->assertStringNotContainsString('internal-provider-detail', $response->getContent());
        Exceptions::assertReported(\RuntimeException::class);
    }
}
