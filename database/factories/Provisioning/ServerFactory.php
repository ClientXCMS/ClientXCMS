<?php

namespace Database\Factories\Provisioning;

use App\Models\Provisioning\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition()
    {
        return [
            'name' => 'Pterodactyl',
            'port' => 443,
            'username' => encrypt('username'),
            'password' => encrypt('password'),
            'type' => 'pterodactyl',
            'address' => 'localhost',
            'hostname' => 'localhost',
            'maxaccounts' => 0,
        ];
    }
}
