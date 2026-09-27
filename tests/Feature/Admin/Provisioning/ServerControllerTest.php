<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\RecordingProductType;
use Tests\Fixtures\RecordingServerType;
use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    const API_URL = 'admin/servers';

    const TEST_ENDPOINT = 'admin/testservers';

    const RECORDING_TYPE = RecordingServerType::UUID;

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

    public function test_admin_server_show_never_renders_stored_credentials(): void
    {
        $id = $this->createServerWithCredentials('Test Server', 'test.com', 'stored-secret-7QZ');

        $response = $this->performAdminAction('GET', self::API_URL."/{$id}");

        $response->assertStatus(200);
        $response->assertDontSee('stored-secret-7QZ');
        $response->assertSee(__('admin.blanktochange'));
        $response->assertSee('autocomplete="new-password"', false);
    }

    public function test_admin_server_show_keeps_domain_env_key_names(): void
    {
        $id = Server::create([
            'name' => 'Registrar',
            'address' => 'registrar.test',
            'hostname' => 'registrar.test',
            'status' => 'active',
            'username' => 'REGISTRAR_API_KEY_ENV',
            'password' => 'REGISTRAR_SECRET_ENV',
            'type' => 'domain',
            'port' => 443,
        ])->id;

        $response = $this->performAdminAction('GET', self::API_URL."/{$id}");

        $response->assertStatus(200);
        $response->assertSee('REGISTRAR_API_KEY_ENV');
        $response->assertSee('REGISTRAR_SECRET_ENV');
    }

    public function test_admin_server_show_without_permission(): void
    {
        $id = $this->createServerWithCredentials();

        $response = $this->performAdminAction('GET', self::API_URL."/{$id}", [], ['admin.manage_products']);

        $response->assertStatus(403);
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
        $id = $this->createServerWithCredentials();
        $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
            'name' => 'Renamed Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'type' => 'none',
            'username' => '',
            'password' => '',
            'port' => 443,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $server = Server::find($id);
        $this->assertEquals('Renamed Server', $server->name);
        $this->assertEquals('aa', $server->username);
        $this->assertEquals('aa', $server->password);
    }

    public function test_admin_server_update_address_without_credentials_is_refused(): void
    {
        $id = $this->createServerWithCredentials();
        $this->performAdminAction('GET', self::API_URL);

        $response = $this->from(self::API_URL."/{$id}")->put(self::API_URL."/{$id}", [
            'name' => 'Test Server',
            'address' => 'test2.com',
            'hostname' => 'test2.com',
            'status' => 'active',
            'type' => 'none',
            'username' => '',
            'password' => '',
            'port' => 443,
        ]);

        $response->assertRedirect(self::API_URL."/{$id}");
        $response->assertSessionHasErrors(['password' => __('provisioning.admin.servers.credentials_required')]);
        $this->assertEquals('test.com', Server::find($id)->address);
    }

    public function test_admin_server_update_without_permission(): void
    {
        $id = $this->createServerWithCredentials();
        $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
            'name' => 'Renamed Server',
            'address' => 'test.com',
            'hostname' => 'test.com',
            'status' => 'active',
            'type' => 'none',
            'port' => 443,
        ], ['admin.manage_products']);
        $response->assertStatus(403);
        $this->assertEquals('Test Server', Server::find($id)->name);
    }

    public function test_admin_server_address_change_without_permission_is_forbidden_whatever_the_stored_credentials(): void
    {
        $withCredentials = $this->createServerWithCredentials();
        $withoutCredentials = $this->createServerWithCredentials('Bare Server', 'bare.test', '');
        foreach ([$withCredentials, $withoutCredentials] as $id) {
            $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
                'name' => 'Renamed Server',
                'address' => 'elsewhere.test',
                'hostname' => 'elsewhere.test',
                'status' => 'active',
                'type' => 'none',
                'port' => 443,
            ], ['admin.manage_products']);
            $response->assertStatus(403);
            $this->assertNotEquals('elsewhere.test', Server::find($id)->address);
        }
    }

    public function test_admin_server_update_with_address_as_array_is_a_validation_error(): void
    {
        $id = $this->createServerWithCredentials();
        $response = $this->performAdminAction('PUT', self::API_URL."/{$id}", [
            'name' => 'Test Server',
            'address' => ['elsewhere.test'],
            'hostname' => 'test.com',
            'status' => 'active',
            'type' => 'none',
            'port' => 443,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['address']);
        $this->assertEquals('test.com', Server::find($id)->address);
    }

    private function createServerWithCredentials(string $name = 'Test Server', string $address = 'test.com', string $secret = 'aa'): int
    {
        return Server::create([
            'name' => $name,
            'address' => $address,
            'hostname' => $address,
            'status' => 'active',
            'username' => $secret,
            'password' => $secret,
            'type' => 'none',
            'port' => 443,
        ])->id;
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

    public function test_admin_server_test_hands_the_stored_credentials_to_the_driver(): void
    {
        $driver = $this->registerRecordingServerType();
        $id = $this->createRecordedServer();

        $response = $this->performAdminAction('GET', self::TEST_ENDPOINT, [
            'server_id' => $id,
            'type' => self::RECORDING_TYPE,
        ]);

        $response->assertStatus(200);
        $this->assertSame('stored-user', $driver->received['username'] ?? null);
        $this->assertSame('stored-secret', $driver->received['password'] ?? null);
    }

    public function test_admin_server_test_never_sends_the_stored_credentials_to_another_address(): void
    {
        $driver = $this->registerRecordingServerType();
        $id = $this->createRecordedServer();

        $response = $this->performAdminAction('GET', self::TEST_ENDPOINT, [
            'server_id' => $id,
            'type' => self::RECORDING_TYPE,
            'address' => 'attacker.example.net',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertSame([], $driver->received);
    }

    private function createRecordedServer(): int
    {
        return Server::create([
            'name' => 'Test Server',
            'address' => 'panel.example.com',
            'hostname' => 'panel.example.com',
            'status' => 'active',
            'username' => 'stored-user',
            'password' => 'stored-secret',
            'type' => self::RECORDING_TYPE,
            'port' => 443,
        ])->id;
    }

    private function registerRecordingServerType(): RecordingServerType
    {
        $driver = new RecordingServerType;
        app('extension')->addProductType(new RecordingProductType($driver));

        return $driver;
    }
}
