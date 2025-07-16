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
 * Year: 2025
 */
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
