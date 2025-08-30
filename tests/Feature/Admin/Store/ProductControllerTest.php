<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Provisioning\Server;
use App\Models\Store\Group;
use App\Modules\Pterodactyl\Models\PterodactylConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    const ROUTE_PREFIX = 'admin/products';

    use RefreshDatabase;

    public function test_admin_store_product_index()
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);
    }

    public function test_admin_store_product_index_with_elements()
    {
        $this->createProductModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);
    }

    public function test_admin_store_product_index_with_invalid_permission()
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, [], ['admin.manage_groups']);
        $response->assertStatus(403);
    }

    public function test_admin_store_product_index_with_search()
    {
        $this->createProductModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, ['filter' => [
            'name' => 'Test Product',
        ]]);
        $response->assertStatus(200);
    }

    public function test_admin_store_product_index_with_pagination()
    {
        $this->createProductModel();
        $this->createProductModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, ['per_page' => 1]);
        $response->assertStatus(200);
    }

    public function test_admin_store_product_create()
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX.'/create');
        $response->assertStatus(200);
    }

    public function test_admin_store_product_store_with_bad_parameters()
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => null,
            'pinned' => false,
        ]);
        $response->assertStatus(422);
    }

    public function test_admin_store_product_store_with_good_parameters()
    {
        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group2',
            'status' => 'active',
        ]);
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
            'pricing' => [
                'monthly' => ['price' => 10, 'setupfee' => 0],
                'quarterly' => ['price' => 20, 'setupfee' => 0],
            ],
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
        $this->assertDatabaseHas('pricings', [
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
            'currency' => 'EUR',
        ]);
    }

    public function test_admin_store_product_with_multiple_pricing()
    {
        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group2',
            'status' => 'active',
        ]);
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
            'pricing' => [
                'monthly' => ['price' => 10, 'setupfee' => 0],
                'quarterly' => ['price' => 20, 'setupfee' => 0],
            ],
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
        $this->assertDatabaseHas('pricings', [
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
            'quarterly' => 20,
            'setup_quarterly' => null,
            'currency' => 'EUR',
        ]);
    }

    public function test_admin_store_product_show()
    {
        $product = $this->createProductModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX.'/'.$product->id);
        $response->assertStatus(200);
    }

    public function test_admin_store_product_show_with_invalid_id()
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX.'/999999');
        $response->assertStatus(404);
    }

    public function test_admin_store_product_update()
    {
        $product = $this->createProductModel();

        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group2',
            'status' => 'active',
            'pricing' => [
                'monthly' => ['price' => 10, 'setupfee' => 0],
                'quarterly' => ['price' => 20, 'setupfee' => 0],
            ],
        ]);
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$product->id, [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
            'pricing' => [
                'monthly' => ['price' => 10, 'setupfee' => 0],
                'quarterly' => ['price' => 20, 'setupfee' => 0],
            ],
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
        $this->assertDatabaseHas('pricings', [
            'related_id' => $product->id,
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
            'quarterly' => 20,
            'setup_quarterly' => null,
            'currency' => 'EUR',
        ]);
    }

    public function test_admin_store_product_update_with_pricing()
    {

        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group2',
            'status' => 'active',
        ]);
        $product = $this->createProductModel();
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$product->id, [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
            'pricing' => [
                'monthly' => ['price' => 10, 'setupfee' => 0],
                'quarterly' => ['price' => 20, 'setupfee' => 0],
            ],
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pricings', [
            'related_id' => $product->id,
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
            'currency' => 'EUR',
        ]);
    }

    public function test_admin_clone_product()
    {
        $product = $this->createProductModel();
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$product->id.'/clone');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', [
            'name' => $product->name.' - Clone',
            'description' => $product->description,
            'status' => $product->status,
            'type' => $product->type,
            'stock' => $product->stock,
            'group_id' => $product->group_id,
        ]);
        $clonedProduct = \App\Models\Store\Product::where('name', $product->name.' - Clone')->first();
        $pricing = $product->pricing[0];
        $this->assertDatabaseHas('pricings', [
            'related_id' => $clonedProduct->id,
            'related_type' => 'product',
            'monthly' => $pricing->monthly,
            'setup_monthly' => $pricing->setup_monthly,
            'quarterly' => $pricing->quarterly,
            'setup_quarterly' => $pricing->setup_quarterly,
        ]);
    }

    public function test_admin_clone_product_with_config()
    {
        $product = $this->createProductModel();
        $server = \App\Models\Provisioning\Server::where('type', 'pterodactyl')->first();
        if ($server == null) {
            $server = Server::create([
                'name' => 'Pterodactyl',
                'port' => 443,
                'username' => encrypt(env('PTERODACTYL_CLIENT_KEY')),
                'password' => encrypt(env('PTERODACTYL_API_KEY')),
                'type' => 'pterodactyl',
                'address' => env('PTERODACTYL_API_URL', 'https://panel.example.com'),
                'hostname' => env('PTERODACTYL_API_URL', 'https://panel.example.com'),
                'maxaccounts' => 0,
            ]);
        }
        if (class_exists(PterodactylConfig::class)) {
            PterodactylConfig::insert([
                'server_id' => $server->id,
                'product_id' => $product->id,
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
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$product->id.'/clone', [
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $clonedProduct = \App\Models\Store\Product::where('name', $product->name.' - Clone')->first();
        $this->assertDatabaseHas('products', [
            'name' => $product->name.' - Clone',
            'description' => $product->description,
            'status' => $product->status,
            'type' => $product->type,
            'stock' => $product->stock,
            'group_id' => $product->group_id,
        ]);
    }

    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Group::truncate();
        \App\Models\Store\Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
