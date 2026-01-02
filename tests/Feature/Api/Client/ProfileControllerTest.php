<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
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

    public function test_customer_can_view_profile(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'firstname',
                    'lastname',
                    'address',
                    'country',
                    'phone',
                    'balance',
                    'two_factor_enabled',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/client/profile');

        $response->assertUnauthorized();
    }

    public function test_customer_can_update_profile(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson('/api/client/profile', [
                'firstname' => 'Updated',
                'lastname' => 'Name',
                'address' => '123 New Street',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('client.profile.updated'),
            ]);

        $customer->refresh();
        $this->assertEquals('Updated', $customer->firstname);
        $this->assertEquals('Name', $customer->lastname);
        $this->assertEquals('123 New Street', $customer->address);
    }

    public function test_customer_can_change_password(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson('/api/client/profile/password', [
                'current_password' => 'password',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('client.profile.changepassword'),
            ]);

        $customer->refresh();
        $this->assertTrue(Hash::check('NewSecurePass123!', $customer->password));
    }

    public function test_customer_cannot_change_password_with_wrong_current_password(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson('/api/client/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_customer_can_get_2fa_setup(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/profile/2fa/setup');

        $response->assertOk()
            ->assertJsonStructure([
                'secret',
                'qr_code',
            ]);
    }

    public function test_customer_with_2fa_enabled_cannot_get_setup(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $customer->twoFactorEnable('TESTSECRET1234567');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/profile/2fa/setup');

        $response->assertBadRequest();
    }

    public function test_customer_can_get_recovery_codes(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $customer->twoFactorEnable('TESTSECRET1234567');

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/profile/2fa/recovery-codes');

        $response->assertOk()
            ->assertJsonStructure([
                'recovery_codes',
            ]);
    }

    public function test_customer_without_2fa_cannot_get_recovery_codes(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/profile/2fa/recovery-codes');

        $response->assertBadRequest();
    }
}
