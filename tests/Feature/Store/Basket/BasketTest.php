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
namespace Tests\Feature\Stores\Basket;

use App\Models\Account\Customer;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Nonstandard\Uuid;
use Tests\TestCase;

class BasketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session()->flush();
        session()->start();

        Basket::$basket = null;
        $uuid = Str::uuid();
        session()->put('basket_uuid', $uuid);
        $basket = Basket::create([
            'user_id' => null,
            'uuid' => $uuid,
            'completed_at' => null,
        ]);
        Basket::$basket = $basket;
    }

    public function test_guest_can_add_product_to_basket()
    {
        $product = $this->createProductModel();
        $response = $this->post(route('front.store.basket.config', ['product' => $product]), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertDatabaseCount('baskets', 1);
        $this->assertCount(1, Basket::getBasket()->rows);
        $this->assertDatabaseCount('baskets_rows', 1);
    }

    public function test_guest_can_add_multiple_products_to_basket()
    {
        $product1 = $this->createProductModel();
        $product2 = $this->createProductModel();

        $response = $this->post(route('front.store.basket.config', $product1), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertRedirect(route('front.store.basket.show'));
        $response = $this->post(route('front.store.basket.config', $product2), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertDatabaseCount('baskets', 1);
        $this->assertCount(2, Basket::getBasket()->rows);
        $this->assertDatabaseCount('baskets_rows', 2);
    }

    public function test_guest_can_add_multiple_products_with_quantities_to_basket()
    {
        $product1 = $this->createProductModel();
        $product2 = $this->createProductModel();

        $response = $this->post(route('front.store.basket.config', $product1), ['quantity' => 1, 'currency' => 'USD', 'billing' => 'monthly']);
        $response->assertRedirect(route('front.store.basket.show'));
        $response = $this->post(route('front.store.basket.config', $product2), ['quantity' => 1, 'currency' => 'USD', 'billing' => 'monthly']);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertDatabaseCount('baskets', 1);
        $this->assertEquals(2, Basket::getBasket()->quantity());
        $this->assertEquals(1, Basket::getBasket()->rows->first()->quantity);
        $this->assertEquals(1, Basket::getBasket()->rows->last()->quantity);
        $this->assertEquals(24, Basket::getBasket()->total());
        $this->assertEquals(4, Basket::getBasket()->tax());
        $this->assertEquals(20, Basket::getBasket()->taxPercent());
        $this->assertEquals(0, Basket::getBasket()->setup());
        // TEST unit price
        $this->assertEquals(10, Basket::getBasket()->items()->first()->recurringPayment(false));
        $this->assertEquals(0, Basket::getBasket()->items()->first()->setup(false));
        $this->assertDatabaseCount('baskets_rows', 2);
    }

    public function test_guest_can_see_basket()
    {
        $response = $this->get(route('front.store.basket.show'));
        $response->assertOk();
    }

    public function test_guest_cannot_add_product_to_basket_because_product_is_hidden()
    {
        $product = $this->createProductModel('hidden');
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.not_valid'), session('error'));
    }

    public function test_cannot_add_product_to_basket_because_product_is_personalized()
    {
        $product = $this->createProductModel();
        $product->attachMetadata('is_personalized_product', 'true');
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.not_valid'), session('error'));
    }

    public function test_cannot_add_product_to_basket_because_product_stock_is_empty()
    {
        $product = $this->createProductModel('active', 0);
        $product->attachMetadata('disabled', 'true');
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.not_valid'), session('error'));
    }

    public function test_cannot_add_product_to_basket_because_product_is_already_ordered1()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $service = $this->createServiceModel($customer->id);
        $service->product_id = $product->id;
        $service->save();
        $product->attachMetadata('allow_only_as_much_services', 'active');
        $response = $this->actingAs($customer)->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 2]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.already_ordered', ['product' => $product->name]), session('error'));
    }

    public function test_cannot_add_product_to_basket_because_product_is_already_ordered2()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $service = $this->createServiceModel($customer->id);
        $service->status = 'active';
        $service->product_id = $product->id;
        $service->save();
        $product->attachMetadata('allow_only_as_much_services', 'all');
        $response = $this->actingAs($customer)->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 2]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.already_ordered', ['product' => $product->name]), session('error'));
    }

    public function test_can_add_product_to_basket_because_product_limitation_is_not_reached()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $service = $this->createServiceModel($customer->id);
        $service->status = 'active';
        $service->product_id = $product->id;
        $service->save();
        $product->attachMetadata('allow_only_as_much_services', 'all:2');
        $response = $this->actingAs($customer)->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 2]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertDatabaseCount('baskets', 1);
        $this->assertCount(1, Basket::getBasket()->rows);
    }

    public function test_guest_cannot_add_product_to_basket_because_product_is_not_valid()
    {
        $product = $this->createProductModel('active', 0);
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.not_valid'), session('error'));
    }

    public function test_guest_cannot_add_product_to_basket_because_product_is_not_in_stock()
    {
        $product = $this->createProductModel('active', 0);
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.not_valid'), session('error'));
    }

    public function test_guest_can_see_basket_with_product()
    {
        $product = $this->createProductModel();
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response = $this->get(route('front.store.basket.show'));
        $response->assertOk();
    }

    public function test_user_can_see_basket_with_product()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->actingAs($customer);
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response = $this->get(route('front.store.basket.show'));
        $response->assertOk();
    }

    public function test_guest_can_change_quantity_plus()
    {
        $product = $this->createProductModel('active', 2);
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response = $this->post(route('front.store.basket.quantity', $product), ['plus' => true]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertEquals(2, Basket::getBasket()->quantity());
    }

    public function test_guest_can_change_quantity_minus()
    {
        $product = $this->createProductModel('active', 10);
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response = $this->post(route('front.store.basket.quantity', $product), ['plus' => true]);
        $response->assertRedirect(route('front.store.basket.show'));
        $response = $this->post(route('front.store.basket.quantity', $product), ['minus' => true]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertEquals(1, Basket::getBasket()->quantity());
    }

    public function test_user_cannot_change_quantity_because_product_is_already_ordered()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 3);
        $product->attachMetadata('disabled_many_services', 'active');
        $this->actingAs($customer);
        $response = $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 2]);
        $response = $this->post(route('front.store.basket.quantity', $product), ['plus' => true]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.basket.already_ordered', ['product' => $product->name]), session('error'));
    }

    public function test_guest_can_change_quantity_delete()
    {
        $product = $this->createProductModel();
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $response = $this->post(route('front.store.basket.quantity', $product), ['minus' => true]);
        $response->assertRedirect(route('front.store.basket.show'));
        $this->assertEquals(0, Basket::getBasket()->quantity());
    }

    public function test_merge_basket()
    {
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $uuid = Uuid::uuid4();
        session()->put('basket_uuid', $uuid);
        // On crée un premier basket offline
        Basket::create([
            'user_id' => null,
            'ipaddress' => request()->ip(),
            'uuid' => $uuid,
            'completed_at' => '2021-01-01 00:00:01',
        ]);
        $basket = Basket::where('uuid', $uuid)->first();
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{json:true}',
        ]);
        // on crée un customer
        $user = Customer::factory()->create();
        $uuid2 = Uuid::uuid4();
        // on crée un basket pour le customer
        $basket = Basket::create([
            'user_id' => $user->id,
            'ipaddress' => request()->ip(),
            'completed_at' => '2021-01-01 00:00:01',
            'uuid' => $uuid2,
        ]);
        $user->attachMetadata('basket_uuid', $uuid2);

        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
        ]);
        $this->assertDatabaseCount('baskets', 1);
        $basket->mergeBasket($user);
        $this->assertDatabaseCount('baskets', 2);
        $this->assertCount(2, $basket->rows);
        $this->assertEquals(4, $basket->quantity());
    }

    public function test_clear_basket()
    {
        $product = $this->createProductModel();
        $this->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        $this->assertEquals(1, Basket::getBasket()->quantity());
        Basket::getBasket()->clear(true);
        $this->assertEquals(0, Basket::getBasket()->quantity());
    }

    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BasketRow::truncate();
        Basket::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
