<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace Tests\Unit\Models\Provisioning;

use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerNotificationVariablesTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_USERNAME = 'root@pam!token-id-that-appears-nowhere-else';

    private const SECRET_PASSWORD = 'c0rrect-horse-battery-staple-unique-to-this-test';

    private function server(): Server
    {
        return Server::factory()->create([
            'name' => 'hv-01',
            'address' => 'hv-01.example.com',
            'type' => 'proxmox',
            'username' => self::SECRET_USERNAME,
            'password' => self::SECRET_PASSWORD,
        ]);
    }

    /**
     * Asserts on the values, not on the key names: renaming the key back in
     * would still fail here, which is the point.
     */
    public function test_no_notification_variable_carries_a_credential(): void
    {
        $variables = $this->server()->getNotificationVariables();

        foreach ($variables as $key => $value) {
            $this->assertNotSame(self::SECRET_USERNAME, $value, "Variable {$key} exposes the server username.");
            $this->assertNotSame(self::SECRET_PASSWORD, $value, "Variable {$key} exposes the server password.");
        }
    }

    public function test_credentials_are_not_offered_as_available_variables(): void
    {
        $offered = Server::getNotificationContextVariables();

        $this->assertNotContains('%server_username%', $offered);
        $this->assertNotContains('%server_password%', $offered);
    }

    /**
     * Everything offered must also be produced, otherwise the admin picks a
     * variable from the list and it ships as literal text in the mail.
     */
    public function test_every_offered_variable_is_actually_produced(): void
    {
        $produced = array_keys($this->server()->getNotificationVariables());

        foreach (Server::getNotificationContextVariables() as $offered) {
            $this->assertContains($offered, $produced, "Variable {$offered} is offered but never produced.");
        }
    }

    public function test_the_harmless_variables_are_still_there(): void
    {
        $variables = $this->server()->getNotificationVariables();

        $this->assertSame('hv-01', $variables['%server_name%']);
        $this->assertSame('hv-01.example.com', $variables['%server_address%']);
        $this->assertSame('proxmox', $variables['%server_type%']);
    }

    /**
     * The cast still decrypts on read, so the model itself is unchanged: only
     * what the mail pipeline is handed has changed.
     */
    public function test_the_model_still_reads_its_own_credentials(): void
    {
        $server = $this->server()->fresh();

        $this->assertSame(self::SECRET_USERNAME, $server->username);
        $this->assertSame(self::SECRET_PASSWORD, $server->password);
    }
}
