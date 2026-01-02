<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/client/auth/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'requires_2fa',
                'customer' => ['id', 'email', 'firstname', 'lastname'],
            ])
            ->assertJson([
                'requires_2fa' => false,
                'token_type' => 'Bearer',
            ]);
    }

    public function test_customer_cannot_login_with_invalid_password(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/client/auth/login', [
            'email' => $customer->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/client/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_banned_customer_cannot_login(): void
    {
        $customer = Customer::factory()->create();
        $admin = $this->createAdminModel();
        $customer->ban('Test ban reason', false, false, $admin);

        $response = $this->postJson('/api/client/auth/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/client/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'country' => 'FR',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'zipcode' => '85000',
            'phone' => '0612345678',
            'region' => 'Test',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'customer' => ['id', 'email', 'firstname', 'lastname'],
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'newuser@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);
    }

    public function test_customer_cannot_register_with_existing_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/client/auth/register', [
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'country' => 'FR',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_cannot_register_when_registration_disabled(): void
    {
        Setting::updateSettings('allow_registration', false);

        $response = $this->postJson('/api/client/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'country' => 'FR',
        ]);

        $response->assertForbidden();
    }

    public function test_customer_can_request_password_reset(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/client/auth/forgot-password', [
            'email' => $customer->email,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.forgot.success'),
            ]);
    }

    public function test_password_reset_returns_success_for_nonexistent_email(): void
    {
        // Should return success to prevent email enumeration
        $response = $this->postJson('/api/client/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.forgot.success'),
            ]);
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $customer = Customer::factory()->create();
        $token = Password::createToken($customer);

        $response = $this->postJson('/api/client/auth/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.reset.success'),
            ]);

        // Verify new password works
        $this->assertTrue(Hash::check('NewPassword123!', $customer->fresh()->password));
    }

    public function test_customer_cannot_reset_password_with_invalid_token(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/client/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $customer->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_cannot_reset_password_when_disabled(): void
    {
        Setting::updateSettings('allow_reset_password', false);
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/client/auth/forgot-password', [
            'email' => $customer->email,
        ]);

        $response->assertForbidden();
    }

    public function test_customer_can_logout(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['*']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
        ])->postJson('/api/client/auth/logout');

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.logout.success'),
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
