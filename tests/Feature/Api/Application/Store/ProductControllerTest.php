<?php

namespace Tests\Feature\Api\Application\Store;

use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    const API_URL = 'api/application/products';

    const ABILITY_INDEX = 'products:index';

    const ABILITY_STORE = 'products:store';

    const ABILITY_SHOW = 'products:show';

    const ABILITY_UPDATE = 'products:update';

    const ABILITY_DELETE = 'products:delete';

    use RefreshDatabase;

    public function test_api_application_product_index(): void
    {
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_product_store(): void
    {
        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
        ]);
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'price' => '10',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
        $response->assertStatus(201);
    }

    public function test_api_application_product_invalid_type_store(): void
    {

        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
        ]);
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'name' => 'Test Product',
            'description' => 'Test Product',
            'price' => '10',
            'status' => 'active',
            'type' => 'invalid',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
        $response->assertStatus(422);
    }

    public function test_api_application_product_get(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('GET', self::API_URL.'/'.$id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_product_delete(): void
    {
        $id = $this->createProduct();

        $response = $this->performAction('DELETE', self::API_URL.'/'.$id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);
    }

    public function test_api_application_product_update(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'name' => 'New Product name',
            'pinned' => true,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'New Product name', 'pinned' => true]);
    }

    public function test_api_application_change_status_product_invalid(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'status' => 'bad',
            'pinned' => true,
        ]);
        $response->assertStatus(422);
    }

    public function test_api_application_change_status_product_valid(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'status' => 'hidden',
            'pinned' => true,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'hidden', 'pinned' => true]);
    }

    public function tes_api_application_product_update_image(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['image' => 'storage/products/test.jpg']);
    }

    public function test_api_application_product_delete_image(): void
    {
        $id = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'remove_image' => 'true',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['image' => null]);
    }

    private function createProduct()
    {
        $group = \App\Models\Store\Group::create([
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
        ]);

        return Product::create([
            'name' => 'Test Product',
            'description' => 'Test Product',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ])->id;
    }
}
