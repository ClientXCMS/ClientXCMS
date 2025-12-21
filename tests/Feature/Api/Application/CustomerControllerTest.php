<?php

namespace Tests\Feature\Api\Application;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    const API_URL = 'api/application/customers';

    const ABILITY_INDEX = 'customers:index';

    const ABILITY_STORE = 'customers:store';

    const ABILITY_SHOW = 'customers:show';

    const ABILITY_UPDATE = 'customers:update';

    const ABILITY_DELETE = 'customers:delete';

    use RefreshDatabase;

    public function test_api_application_customer_index(): void
    {
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_customer_filter(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAction('GET', self::API_URL . '?filter[firstname]=Fake', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_api_application_customer_sort()
    {

        Customer::factory(15)->create();
        $lastCustomer = Customer::orderBy('id', 'desc')->first();
        $response = $this->performAction('GET', self::API_URL . '?sort=-id', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($lastCustomer->id, $response->json('data.0.id'));
    }

    public function test_api_application_customer_store(): void
    {
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0223456789',
            'password' => 'password',
            'locale' => 'fr_FR',
        ]);
        $response->assertStatus(201);
    }

    public function test_api_application_customer_verified_store(): void
    {
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
            'verified' => '1',
            'locale' => 'fr_FR',
        ]);
        $response->assertStatus(201);
        $response->assertJsonFragment(['is_confirmed' => true]);
    }

    public function test_api_application_customer_get(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAction('GET', self::API_URL . '/' . $id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_customer_delete(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAction('DELETE', self::API_URL . '/' . $id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);
    }

    public function test_api_application_customer_update(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAction('POST', self::API_URL . '/' . $id, [self::ABILITY_UPDATE], [
            'email' => 'admin@administration.com',
            'city' => 'roubaix',
            'firstname' => 'Martin',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'address' => 'test',
            'phone' => '0323456789',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'admin@administration.com', 'city' => 'roubaix', 'firstname' => 'Martin']);
    }

    public function test_api_application_customer_confirm(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->performAction('POST', self::API_URL . '/' . $customer->id . '/confirm', [self::ABILITY_UPDATE]);
        $response->assertStatus(200);
        $this->assertTrue($customer->fresh()->hasVerifiedEmail());
    }

    public function test_api_application_customer_resend_confirmation(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->performAction('post', self::API_URL . '/' . $customer->id . '/resend_confirmation', [self::ABILITY_UPDATE]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Verification link sent']);
    }

    public function test_api_application_customer_send_forgot_password(): void
    {
        $customer = Customer::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->performAction('post', self::API_URL . '/' . $customer->id . '/send_password', [self::ABILITY_UPDATE]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Reset link sent']);
    }

    public function test_api_application_customer_suspend_action(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->performAction('POST', self::API_URL . '/' . $customer->id . '/action/suspend', [self::ABILITY_UPDATE], [
            'reason' => 'Violation',
            'force' => true,
            'notify' => false,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($customer->fresh()->isSuspended());
    }

    public function test_api_application_customer_reactivate_action(): void
    {
        $customer = Customer::factory()->create();
        $customer->suspend('Test reason', false, false);
        $response = $this->performAction('POST', self::API_URL . '/' . $customer->id . '/action/reactivate', [self::ABILITY_UPDATE], [
            'notify' => true,
        ]);

        $response->assertStatus(200);
        $this->assertFalse($customer->fresh()->isSuspended());
    }

    public function test_api_application_customer_ban_action(): void
    {
        $customer = Customer::factory()->create();
        $customer->ban('Test reason', false, false);
        $response = $this->performAction('POST', self::API_URL . '/' . $customer->id . '/action/ban', [self::ABILITY_UPDATE], [
            'reason' => 'Abuse',
            'force' => false,
            'notify' => true,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($customer->fresh()->isBanned());
    }

    public function test_api_application_customer_disable_2fa_action(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('Test secret');

        $response = $this->performAction('POST', self::API_URL . '/' . $customer->id . '/action/disable2FA', [self::ABILITY_UPDATE]);
        $response->assertStatus(200);
        $this->assertEquals(0, $customer->fresh()->twoFactorEnabled());
    }
}
