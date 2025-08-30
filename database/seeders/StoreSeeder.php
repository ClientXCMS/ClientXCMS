<?php

namespace Database\Seeders;

use App\Models\Provisioning\Server;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Modules\Pterodactyl\Models\PterodactylConfig;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Group::count() > 0) {
            return;
        }

        $id = Group::create([
            'name' => 'Minecraft',
            'slug' => 'minecraft',
            'description' => 'Minecraft',
            'sort_order' => 1,
        ])->id;
        $product = Product::create([
            'name' => 'Minecraft Charbon',
            'group_id' => $id,
            'status' => 'active',
            'description' => 'Minecraft Charbon',
            'sort_order' => 1,
            'pinned' => 1,
            'stock' => 10,
            'type' => 'pterodactyl',
        ])->id;
        Pricing::create([
            'related_id' => $product,
            'related_type' => 'product',
            'currency' => 'EUR',
            'monthly' => 9.99,
            'quarterly' => 29.99,
            'semiannually' => 59.99,
            'setup_monthly' => 4.99,
        ]);
        $server = Server::where('type', 'pterodactyl')->first();
        if ($server == null) {
            return;
        }
        app('extension')->autoload(app());
        \Artisan::call('migrate');

        return;
        if (class_exists(PterodactylConfig::class)) {

            PterodactylConfig::insert([
                'server_id' => $server->id,
                'product_id' => $product,
                'memory' => 1,
                'swap' => 0,
                'disk' => 1,
                'io' => 10,
                'cpu' => 1,
                'location_id' => 1,
                'server_description' => '%service_expiration%',
                'server_name' => 'Minecraft Charbon',
                'eggs' => json_encode(['1'.PterodactylConfig::DELIMITER.'1']),
            ]);
        }
    }
}
