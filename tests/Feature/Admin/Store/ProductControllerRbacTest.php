<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Store\Group;
use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $group = Group::create(['name' => 'pentest-group', 'slug' => 'pentest-group', 'description' => 'pg', 'sort_order' => 1, 'status' => 'active']);

        return Product::create([
            'name' => 'pentest-product',
            'description' => 'p',
            'group_id' => $group->id,
            'status' => 'active',
            'stock' => 0,
            'type' => 'none',
            'sort_order' => 1,
        ]);
    }

    public function test_destroy_blocks_admin_without_permission(): void
    {
        $product = $this->product();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.products.destroy', $product),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_store_form_request_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.products.store'),
            ['name' => 'p', 'type' => 'none'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_destroy_allows_admin_with_manage_products(): void
    {
        $product = $this->product();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.products.destroy', $product),
            [],
            ['admin.manage_products']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_PRODUCTS must not be blocked');
    }
}
