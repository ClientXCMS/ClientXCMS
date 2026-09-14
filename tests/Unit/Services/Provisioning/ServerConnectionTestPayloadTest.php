<?php

namespace Tests\Unit\Services\Provisioning;

use App\Exceptions\CustomTargetRequiresCredentialsException;
use App\Models\Provisioning\Server;
use App\Services\Provisioning\ServerConnectionTestPayload;
use Tests\TestCase;

class ServerConnectionTestPayloadTest extends TestCase
{
    private ServerConnectionTestPayload $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = new ServerConnectionTestPayload;
    }

    public function test_it_returns_the_input_untouched_without_a_stored_server(): void
    {
        $input = ['address' => 'panel.example.com', 'username' => '', 'password' => ''];

        $this->assertSame($input, $this->payload->resolve($input, null));
    }

    public function test_it_borrows_the_stored_credentials_for_the_stored_host(): void
    {
        $resolved = $this->payload->resolve(['username' => '', 'password' => ''], $this->storedServer());

        $this->assertSame('stored-user', $resolved['username']);
        $this->assertSame('stored-secret', $resolved['password']);
        $this->assertSame('panel.example.com', $resolved['address']);
        $this->assertSame(8006, $resolved['port']);
    }

    public function test_it_borrows_the_stored_credentials_when_the_address_only_differs_by_case_or_spacing(): void
    {
        $resolved = $this->payload->resolve(['address' => '  Panel.Example.COM ', 'password' => ''], $this->storedServer());

        $this->assertSame('stored-secret', $resolved['password']);
    }

    public function test_it_refuses_to_send_the_stored_credentials_to_another_address(): void
    {
        $this->expectException(CustomTargetRequiresCredentialsException::class);

        $this->payload->resolve(['address' => 'attacker.example.net', 'username' => '', 'password' => ''], $this->storedServer());
    }

    public function test_it_refuses_to_send_the_stored_credentials_to_another_hostname(): void
    {
        $this->expectException(CustomTargetRequiresCredentialsException::class);

        $this->payload->resolve(['hostname' => 'attacker.example.net', 'username' => '', 'password' => ''], $this->storedServer());
    }

    public function test_it_keeps_the_caller_credentials_when_testing_another_address(): void
    {
        $resolved = $this->payload->resolve([
            'address' => 'staging.example.com',
            'username' => 'caller-user',
            'password' => 'caller-secret',
        ], $this->storedServer());

        $this->assertSame('staging.example.com', $resolved['address']);
        $this->assertSame('caller-user', $resolved['username']);
        $this->assertSame('caller-secret', $resolved['password']);
    }

    private function storedServer(): Server
    {
        $server = new Server;
        $server->fill([
            'name' => 'Stored server',
            'address' => 'panel.example.com',
            'hostname' => 'panel.example.com',
            'username' => 'stored-user',
            'password' => 'stored-secret',
            'port' => 8006,
            'type' => 'none',
        ]);

        return $server;
    }
}
