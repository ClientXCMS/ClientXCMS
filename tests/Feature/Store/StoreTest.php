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
namespace Store;

use Illuminate\Foundation\Testing\RefreshDatabase;

class StoreTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_store_index(): void
    {
        $group = $this->createGroupModel();
        $response = $this->get(route('front.store.index'));
        $response->assertStatus(200);
    }

    public function test_show_group(): void
    {
        $group = $this->createGroupModel();
        $response = $this->get(route('front.store.group', ['group' => $group->slug]));
        $response->assertStatus(200);
    }

    public function test_show_unreferenced_group_admin(): void
    {
        $group = $this->createGroupModel('unreferenced');
        $admin = $this->createAdminModel();
        $response = $this->actingAs($admin, 'admin')->get(route('front.store.group', ['group' => $group->slug]));
        $response->assertStatus(200);
    }

    public function test_show_unreferenced_group_customer(): void
    {
        $group = $this->createGroupModel('unreferenced');
        $customer = $this->createCustomerModel();
        $response = $this->actingAs($customer)->get(route('front.store.group', ['group' => $group->slug]));
        $response->assertStatus(404);
    }

    public function test_show_hidden_group(): void
    {
        $group = $this->createGroupModel('hidden');
        $response = $this->get(route('front.store.group', ['group' => $group->slug]));
        $response->assertStatus(404);
    }

    public function test_show_subgroup(): void
    {
        $group = $this->createGroupModel();
        $subgroup = $this->createGroupModel('active', $group->id);
        $response = $this->get(route('front.store.group', ['group' => $group->slug, 'subgroup' => $subgroup->slug]));
        $response->assertStatus(200);
    }

    public function test_show_subgroup_with_hidden_parent(): void
    {
        $group = $this->createGroupModel('hidden');
        $subgroup = $this->createGroupModel('active', $group->id);
        $response = $this->get(route('front.store.group', ['group' => $group->slug, 'subgroup' => $subgroup->slug]));
        $response->assertStatus(404);
    }

    public function test_show_subgroup_with_hidden_parent_and_visible_subgroup(): void
    {
        $group = $this->createGroupModel('hidden');
        $subgroup = $this->createGroupModel('active', $group->id);
        $response = $this->get(route('front.store.group', ['group' => $group->slug, 'subgroup' => $subgroup->slug]));
        $response->assertStatus(404);
    }

    public function test_show_subgroup_with_product(): void
    {
        $group = $this->createGroupModel();
        $product = $this->createProductModel();
        $subgroup = $this->createGroupModel('active', $group->id);
        $product->group_id = $subgroup->id;
        $product->save();
        $response = $this->get(route('front.store.group', ['group' => $group->slug, 'subgroup' => $subgroup->slug]));
        $response->assertStatus(200);
    }
}
