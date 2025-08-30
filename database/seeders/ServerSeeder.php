<?php

namespace Database\Seeders;

use App\Models\Provisioning\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (\App::isProduction()) {
            return;
        }
        if (Server::count() > 0 || ! env('PTERODACTYL_API_KEY') || ! env('PTERODACTYL_API_URL')) {
            return;
        }

        Server::insert([
            'name' => 'Pterodactyl',
            'port' => 443,
            'username' => encrypt(env('PTERODACTYL_CLIENT_KEY')),
            'password' => encrypt(env('PTERODACTYL_API_KEY')),
            'type' => 'pterodactyl',
            'address' => env('PTERODACTYL_API_URL'),
            'hostname' => env('PTERODACTYL_API_URL'),
            'maxaccounts' => 0,
        ]);

    }
}
