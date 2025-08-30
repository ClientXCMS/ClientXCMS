<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    const API_URL = 'admin/servers';

    const TEST_ENDPOINT = 'admin/testservers';

    use RefreshDatabase;

    public function test_admin_server_index(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_server_delete(): void
    {
        $id = Server::create([
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'type' => 'none',
            'port' => 443,
        ])->id;
        $response = $this->performAdminAction('DELETE', self::API_URL."/{$id}");
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_server_index_without_permission(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_server_get(): void
    {
        $id = Server::create([
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'type' => 'none',
            'port' => 443,
        ])->id;
        $response = $this->performAdminAction('GET', self::API_URL."/{$id}");
        $response->assertStatus(200);

    }

    public function test_admin_server_update(): void
    {
        $id = Server::create([
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'type' => 'none',
            'port' => 443,
        ])->id;
        $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
            'name' => 'Test Server',
            'address' => 'test2.com',
            'hostname' => 'test2.com',
            'status' => 'active',
            'type' => 'none',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'port' => 443,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

    }

    public function test_admin_server_create(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_server_store(): void
    {
        $response = $this->performAdminAction('POST', self::API_URL, [
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'type' => 'none',
            'port' => 443,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_server_update_without_credentials(): void
    {
        $id = Server::create([
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'aa',
            'password' => 'aa',
            'type' => 'none',
            'port' => 443,
        ])->id;
        $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
            'name' => 'Test Server',
            'address' => 'test2.com',
            'hostname' => 'test2.com',
            'status' => 'active',
            'type' => 'none',
            'username' => '',
            'password' => '',
            'port' => 443,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $server = Server::find($id);
        $this->assertEquals('aa', $server->username);
        $this->assertEquals('aa', $server->password);
        // assertt does not change
    }

    public function test_admin_server_test_bad_parameters(): void
    {
        $response = $this->performAdminAction('GET', self::TEST_ENDPOINT, [
            'server_id' => -1,
        ]);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Server not found',
        ]);
    }

    public function test_admin_server_test_simple_successfully(): void
    {
        $response = $this->performAdminAction('GET', self::TEST_ENDPOINT, [
            'address' => 'localhost',
            'hostname' => 'localhost',
            'username' => '',
            'password' => '',
            'port' => 443,
            'type' => 'none',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_server_test_server_id_successfully(): void
    {
        $id = Server::create([
            'name' => 'Test Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'username' => 'XXXX',
            'password' => 'XXXX',
            'type' => 'none',
            'port' => 443,
        ])->id;
        $response = $this->performAdminAction('GET', self::TEST_ENDPOINT, [
            'server_id' => $id,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
