<?php

namespace Store\Basket;

use App\Models\Account\Customer;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_coupon_to_basket()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->createBasket($user);
        $basket = Basket::getBasket();
        $basketRow = BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'options' => json_encode([]),
        ]);
        $coupon = $this->createCoupon();
        $response = $this->be($user)->post(route('front.store.basket.coupon'), [
            'coupon' => $coupon->code,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success', __('coupon.coupon_applied'));
        $this->assertDatabaseHas('baskets', [
            'id' => $basket->id,
            'coupon_id' => $coupon->id,
        ]);
    }

    protected function createBasket(?Customer $customer = null)
    {
        $basket = Basket::create([
            'user_id' => $customer ? $customer->id : null,
            'ipaddress' => request()->ip(),
            'uuid' => Uuid::uuid4(),
        ]);
        BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $this->createProductModel()->id,
            'quantity' => 1,
            'billing' => 'monthly',
        ]);
        if ($customer) {
            $customer->attachMetadata('basket_uuid', $basket->uuid);
        } else {
            session()->put('basket_uuid', $basket->uuid);
        }
    }
}
