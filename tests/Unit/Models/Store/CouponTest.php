<?php

namespace Tests\Unit\Models\Store;

use App\Models\Billing\Invoice;
use App\Models\Store\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_max_usages()
    {
        $customer = $this->createCustomerModel();
        $coupon = Coupon::factory()->create(['max_uses' => 1]);
        $basket = $this->createBasketForCustomer($customer);

        $coupon->usages()->create(['customer_id' => $basket->user_id, 'used_at' => now(), 'amount' => 10]);

        $this->assertFalse($coupon->isValid($basket));
        $this->assertEquals(Session::get('error'), __('coupon.coupon_max_uses'));
    }

    public function test_coupon_first_order_only()
    {
        $customer = $this->createCustomerModel();
        $basket = $this->createBasketForCustomer($customer);
        $coupon = Coupon::factory()->create(['first_order_only' => true]);
        Invoice::factory(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PAID])->create();
        $this->assertFalse($coupon->isValid($basket));
        $this->assertEquals(Session::get('error'), __('coupon.first_order_only'));
    }

    public function test_coupon_expired()
    {
        $customer = $this->createCustomerModel();
        $coupon = $this->createCoupon(['end_at' => now()->subDay()]);
        $basket = $this->createBasketForCustomer($customer);

        $this->assertFalse($coupon->isValid($basket));
        $this->assertEquals(Session::get('error'), __('coupon.coupon_expired'));
    }

    public function test_coupon_free_setup()
    {
        $customer = $this->createCustomerModel();
        $coupon = $this->createCoupon(['free_setup' => true, 'is_global' => true], ['monthly' => 0, 'setup_monthly' => 10]);
        $basket = $this->createBasketForCustomer($customer);
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertEquals(0, $basket->setup());
        $this->assertEquals(10, $basket->subtotal());
        $this->assertEquals(10, $basket->discount());
    }

    public function test_coupon_minimum_order_amount()
    {
        $customer = $this->createCustomerModel();
        $coupon = Coupon::factory()->create(['minimum_order_amount' => 100]);
        $basket = $this->createBasketForCustomer($customer);
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $this->assertFalse($coupon->isValid($basket));
        $this->assertEquals(Session::get('error'), __('coupon.minimum_order_amount', ['amount' => formatted_price(100)]));
    }

    public function test_coupon_products_required()
    {
        $customer = $this->createCustomerModel();
        $productRequired = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['products_required' => [$productRequired->id]]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $this->assertFalse($coupon->isValid($basket));
        $this->assertEquals(Session::get('error'), __('coupon.coupon_not_valid_product'));
    }

    public function test_coupon_valid_with_all_conditions_met()
    {
        $customer = $this->createCustomerModel();
        $requiredProduct = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = Coupon::factory()->create([
            'max_uses' => 5,
            'first_order_only' => false,
            'end_at' => now()->addDay(),
            'minimum_order_amount' => 30,
            'products_required' => [$requiredProduct->id],
        ]);

        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->rows()->create(['product_id' => $requiredProduct->id, 'quantity' => 1, 'billing' => 'monthly']);

        $this->assertTrue($coupon->isValid($basket));
    }

    public function test_coupon_valid_with_globally_percentage()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => true], ['monthly' => 10, 'setup_monthly' => 10]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertTrue($coupon->isValid($basket));
        $this->assertEquals(18, $basket->subtotal());
        $this->assertEquals(9, $basket->setup());
        $this->assertEquals(2, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
        $this->assertEquals(2, $basket->discount());
    }

    public function test_coupon_valid_with_globally_fixed()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => true, 'type' => 'fixed'], ['monthly' => 5]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertTrue($coupon->isValid($basket));
        $this->assertEquals(15, $basket->subtotal());
        $this->assertEquals(10, $basket->setup());
        $this->assertEquals(5, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
    }

    public function test_coupon_valid_with_globally_multiple_products_percentages()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product2 = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => true, 'type' => 'fixed'], ['monthly' => 5, 'setup_monthly' => 5]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product2->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertTrue($coupon->isValid($basket));
        $this->assertEquals(20, $basket->subtotal());
        $this->assertEquals(10, $basket->setup());
        $this->assertEquals(20, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
    }

    public function test_coupon_valid_with_globally_multiples_quantities_percentages()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product2 = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => true, 'type' => 'fixed'], ['monthly' => 5]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product2->id, 'quantity' => 2, 'billing' => 'monthly']);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 2, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertTrue($coupon->isValid($basket));
        $this->assertEquals(70, $basket->subtotal());
        $this->assertEquals(40, $basket->setup());
        $this->assertEquals(10, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
    }

    public function test_coupon_valid_with_simple_product()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product2 = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => false], ['monthly' => 5]);
        $coupon->products()->attach($product);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product2->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        $this->assertTrue($coupon->isValid($basket));
        $this->assertEquals(39.50, $basket->subtotal());
        $this->assertEquals(20, $basket->setup());
        $this->assertEquals(0.50, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
    }

    public function test_coupon_invalid_with_different_billing()
    {
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $product2 = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $coupon = $this->createCoupon(['is_global' => true], ['monthly' => 5]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $product2->id, 'quantity' => 1, 'billing' => 'yearly']);
        $basket->rows()->create(['product_id' => $product->id, 'quantity' => 1, 'billing' => 'monthly']);
        $basket->applyCoupon($coupon->code);
        // only monthly product must be discounted
        $this->assertEquals(39.50, $basket->subtotal());
        $this->assertEquals(20, $basket->setup());
        $this->assertEquals(0.50, $basket->discount());
        $this->assertEquals($coupon->id, $basket->coupon_id);
    }
}
