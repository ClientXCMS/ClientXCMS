<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Store\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    const ROUTE_PREFIX = 'admin/groups';

    use RefreshDatabase;

    public function test_admin_store_group_index(): void
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);
    }

    public function test_admin_store_group_index_with_elements(): void
    {
        $this->createGroupModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);
    }

    public function test_admin_store_group_index_with_invalid_permission(): void
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_store_group_index_with_search(): void
    {
        $group = $this->createGroupModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, ['q' => 'aaa']);
        $response->assertStatus(200);
    }

    public function test_admin_store_group_index_with_pagination(): void
    {
        $this->createGroupModel();
        $this->createGroupModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, ['per_page' => 1]);
        $response->assertStatus(200);
    }

    public function test_admin_store_group_create(): void
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX.'/create');
        $response->assertStatus(200);
    }

    public function test_admin_store_group_store_with_bad_parameters(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group2',
            'status' => 'active',
            'pinned' => false,
            'sort_order' => 1,
            'group_id' => null,
        ]);
        $response->assertStatus(302);
    }

    public function test_admin_store_group_store_with_good_parameters(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
            'pinned' => false,
            'sort_order' => 1,
            'group_id' => null,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_store_group_show(): void
    {
        $group = $this->createGroupModel();
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX.'/'.$group->id);
        $response->assertStatus(200);
    }

    public function test_admin_store_group_update(): void
    {
        $group = $this->createGroupModel();
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$group->id, [
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
            'pinned' => false,
            'sort_order' => 1,
            'parent_id' => null,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_store_group_destroy(): void
    {
        $group = $this->createGroupModel();
        $response = $this->performAdminAction('DELETE', self::ROUTE_PREFIX.'/'.$group->id);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_store_group_destroy_with_products(): void
    {
        $group = $this->createGroupModel();
        $product = $this->createProductModel('active', 1, ['monthly' => 10]);
        $product->update([
            'group_id' => $group->id,
        ]);
        $response = $this->performAdminAction('DELETE', self::ROUTE_PREFIX.'/'.$group->id);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_store_group_clone(): void
    {
        $group = $this->createGroupModel();
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$group->id.'/clone');
        $response->assertStatus(302);
        $second = Group::orderBy('id', 'desc')->first();
        $this->assertEquals($group->name.' - '.__('global.clone'), $second->name);
    }

    public function test_admin_store_group_clone_with_products()
    {
        $group = $this->createGroupModel();
        $product = $this->createProductModel('active', 1, ['monthly' => 10]);
        $product->update(['group_id' => $group->id]);
        $response = $this->performAdminAction('PUT', self::ROUTE_PREFIX.'/'.$group->id.'/clone', ['with' => true]);
        $response->assertStatus(302);
        $second = Group::orderBy('id', 'desc')->first();
        $this->assertEquals($group->name.' - '.__('global.clone'), $second->name);
        $this->assertCount(2, Group::all());
        $newProduct = $second->products()->first();
        $this->assertDatabaseHas('products', [
            'id' => $newProduct->id,
            'group_id' => $second->id,
        ]);
        $this->assertDatabaseHas('pricings', [
            'related_id' => $newProduct->id,
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
            'currency' => 'USD',
        ]);
    }

    public function test_admin_store_group_sort(): void
    {
        $product1 = $this->createProductModel('active', 1, ['monthly' => 10]);
        $product2 = $this->createProductModel('active', 1, ['monthly' => 10]);
        $product3 = $this->createProductModel('active', 1, ['monthly' => 10]);
        $product1->update(['sort_order' => 1]);
        $product2->update(['sort_order' => 2]);
        $product3->update(['sort_order' => 3]);
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX.'/sort', [
            'items' => [$product3->id, $product2->id, $product1->id],
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $product2->refresh();
        $product1->refresh();
        $product3->refresh();
        $this->assertEquals(2, $product1->sort_order);
        $this->assertEquals(1, $product2->sort_order);
        $this->assertEquals(0, $product3->sort_order);
    }

    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Group::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
