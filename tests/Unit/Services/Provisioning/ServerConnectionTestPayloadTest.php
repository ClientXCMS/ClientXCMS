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

    public function test_credentials_are_required_for_another_address_or_hostname(): void
    {
        $server = $this->storedServer();

        $this->assertTrue($this->payload->requiresCredentials(['address' => 'attacker.example.net'], $server));
        $this->assertTrue($this->payload->requiresCredentials(['hostname' => 'attacker.example.net'], $server));
    }

    public function test_credentials_are_not_required_for_the_stored_destination(): void
    {
        $server = $this->storedServer();

        $this->assertFalse($this->payload->requiresCredentials([], $server));
        $this->assertFalse($this->payload->requiresCredentials(['address' => '', 'hostname' => '  '], $server));
        $this->assertFalse($this->payload->requiresCredentials(['address' => '  Panel.Example.COM ', 'hostname' => 'PANEL.example.com', 'port' => 22], $server));
    }

    public function test_credentials_are_not_required_when_the_server_stores_none(): void
    {
        $server = $this->storedServer(['username' => null, 'password' => null]);

        $this->assertFalse($this->payload->requiresCredentials(['address' => 'elsewhere.example.net'], $server));
    }

    public function test_credentials_are_required_when_the_server_stores_only_one_of_them(): void
    {
        $server = $this->storedServer(['username' => null]);

        $this->assertTrue($this->payload->requiresCredentials(['address' => 'elsewhere.example.net'], $server));
    }

    public function test_credentials_count_only_when_both_username_and_password_are_given(): void
    {
        $this->assertTrue($this->payload->hasCredentials(['username' => 'u', 'password' => 'p']));
        $this->assertFalse($this->payload->hasCredentials(['username' => '', 'password' => 'p']));
        $this->assertFalse($this->payload->hasCredentials(['username' => 'u', 'password' => ' ']));
        $this->assertFalse($this->payload->hasCredentials(['password' => 'p']));
    }

    private function storedServer(array $overrides = []): Server
    {
        $server = new Server;
        $server->fill(array_merge([
            'name' => 'Stored server',
            'address' => 'panel.example.com',
            'hostname' => 'panel.example.com',
            'username' => 'stored-user',
            'password' => 'stored-secret',
            'port' => 8006,
            'type' => 'none',
        ], $overrides));

        return $server;
    }
}
