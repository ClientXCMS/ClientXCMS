<?php

namespace Tests\Feature;

use App\Models\Provisioning\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_decrypt_password(): void
    {
        $server = Server::factory()->create();
        $server = Server::find($server->id);
        $this->assertEquals('password', decrypt($server->password));

    }
}
