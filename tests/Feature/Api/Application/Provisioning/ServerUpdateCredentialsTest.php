<?php

namespace Tests\Feature\Api\Application\Provisioning;

use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerUpdateCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_the_address_without_credentials_is_refused(): void
    {
        $server = $this->storedServer();

        $response = $this->performAction('POST', $this->url($server), ['servers:update'], [
            'address' => 'elsewhere.example.test',
            'hostname' => 'elsewhere.example.test',
            'port' => 443,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password' => __('provisioning.admin.servers.credentials_required')]);
        $server->refresh();
        $this->assertSame('panel.example.test', $server->address);
        $this->assertSame('panel.example.test', $server->hostname);
    }

    public function test_an_update_on_the_same_destination_keeps_the_stored_credentials(): void
    {
        $server = $this->storedServer();

        $response = $this->performAction('POST', $this->url($server), ['servers:update'], [
            'name' => 'Renamed server',
            'address' => 'panel.example.test',
            'hostname' => 'panel.example.test',
            'port' => 8443,
        ]);

        $response->assertStatus(200);
        $server->refresh();
        $this->assertSame('Renamed server', $server->name);
        $this->assertSame(8443, (int) $server->port);
        $this->assertSame('stored-user', $server->username);
        $this->assertSame('stored-secret', $server->password);
    }

    public function test_changing_the_address_with_credentials_is_accepted(): void
    {
        $server = $this->storedServer();

        $response = $this->performAction('POST', $this->url($server), ['servers:update'], [
            'address' => 'elsewhere.example.test',
            'hostname' => 'elsewhere.example.test',
            'port' => 443,
            'username' => 'new-user',
            'password' => 'new-secret',
        ]);

        $response->assertStatus(200);
        $server->refresh();
        $this->assertSame('elsewhere.example.test', $server->address);
        $this->assertSame('new-user', $server->username);
        $this->assertSame('new-secret', $server->password);
    }

    public function test_a_token_without_the_update_ability_is_refused(): void
    {
        $server = $this->storedServer();

        $response = $this->performAction('POST', $this->url($server), ['servers:index'], [
            'address' => 'panel.example.test',
            'hostname' => 'panel.example.test',
            'port' => 443,
        ]);

        $response->assertStatus(403);
    }

    private function storedServer(): Server
    {
        return Server::create([
            'name' => 'Stored server',
            'address' => 'panel.example.test',
            'hostname' => 'panel.example.test',
            'status' => 'active',
            'username' => 'stored-user',
            'password' => 'stored-secret',
            'type' => 'none',
            'port' => 443,
        ]);
    }

    private function url(Server $server): string
    {
        return "api/application/servers/{$server->id}";
    }
}
