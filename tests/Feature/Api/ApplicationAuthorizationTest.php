<?php

namespace Tests\Feature\Api;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_session_cannot_access_application_api(): void
    {
        $this->actingAs(Customer::factory()->create(), 'web');

        $this->getJson('/api/application/customers')->assertForbidden();
        $this->getJson('/api/application/servers')->assertForbidden();
        $this->postJson('/api/application/customers/1/action/disable2FA')->assertForbidden();
    }

    public function test_customer_wildcard_token_cannot_access_application_api(): void
    {
        $token = Customer::factory()->create()->createToken('customer', ['*'])->plainTextToken;

        $this->withToken($token)->getJson('/api/application/servers')->assertForbidden();
    }

    public function test_admin_token_still_requires_its_ability(): void
    {
        $admin = Admin::factory()->create();
        $token = $admin->createToken('limited', ['customers:index'])->plainTextToken;

        $this->withToken($token)->getJson('/api/application/servers')->assertForbidden();
    }

    public function test_admin_token_with_matching_ability_can_access_application_api(): void
    {
        $admin = Admin::factory()->create();
        $token = $admin->createToken('servers', ['servers:index'])->plainTextToken;

        $this->withToken($token)->getJson('/api/application/servers')->assertOk();
    }

    public function test_server_credentials_are_not_serialized(): void
    {
        $server = new Server;
        $server->username = 'operator';
        $server->password = 'secret';

        $this->assertArrayNotHasKey('username', $server->toArray());
        $this->assertArrayNotHasKey('password', $server->toArray());
    }
}
