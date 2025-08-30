<?php

namespace Tests;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Admin\Permission;
use App\Models\Billing\Gateway;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Coupon;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function performAction(string $method, string $url, array $abbilities = ['*'], array $data = []): TestResponse
    {
        $this->seed(AdminSeeder::class);
        $token = Admin::first()->createToken('test-admin', $abbilities);
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token->plainTextToken, 'Accept' => 'application/json'])->json($method, $url, $data);
        Admin::first()->tokens()->delete();

        return $response;
    }

    protected function performAdminAction(string $method, string $url, array $data = [], array $permissions = ['*']): TestResponse
    {
        $this->seed(AdminSeeder::class);
        if ($permissions[0] != '*') {
            $role = Admin::first()->role;
            $this->seed(PermissionSeeder::class);
            $permissions = Permission::whereIn('name', $permissions)->pluck('id');
            $role->permissions()->sync($permissions);
            $role->is_admin = false;
            $role->save();
        } else {
            $role = Admin::first()->role;
            if ($role->permissions()->count() > 0) {
                $role->permissions()->detach();
            }
            $role->is_admin = true;
            $role->save();
        }
        $admin = Admin::first();

        return $this->be($admin, 'admin')->json($method, $url, $data);
    }

    protected function createGroupModel(string $status = 'active', ?int $groupId = null)
    {
        $group = new Group;
        $group->name = 'Test Group';
        $group->slug = 'test-slug-'.'-'.rand(1, 1000);
        $group->description = 'Test Group Description';
        $group->status = $status;
        $group->parent_id = $groupId;
        $group->save();
        $group->update(['slug' => 'test-slug-'.$group->id]);

        return $group;
    }

    protected function createGatewayModel()
    {
        $gateway = new Gateway;
        $gateway->name = 'Test Gateway';
        $gateway->uuid = 'balance';
        $gateway->status = 'active';
        $gateway->id = 1;
        $gateway->save();

        return $gateway;
    }

    protected function createProductModel(string $status = 'active', int $stock = 1, array $prices = ['monthly' => 10])
    {
        if (Group::count() == 0) {
            $this->createGroupModel();
        }
        $product = new Product;
        $product->name = 'Test Product';
        $product->status = $status;
        $product->description = 'Test Product Description';
        $product->sort_order = 1;
        $product->group_id = Group::first()->id;
        $product->stock = $stock;
        $product->type = 'none';
        $product->save();
        if (! empty($prices)) {
            $this->createPriceModel($product->id, 'USD', $prices);
        }

        return $product;
    }

    protected function createProductModelWithOption(string $status = 'active', int $stock = 1, array $prices = ['monthly' => 10])
    {
        if (Group::count() == 0) {
            $this->createGroupModel();
        }
        $product = new Product;
        $product->name = 'Test Product';
        $product->status = $status;
        $product->description = 'Test Product Description';
        $product->sort_order = 1;
        $product->group_id = Group::first()->id;
        $product->stock = $stock;
        $product->type = 'none';
        $product->save();
        $this->createPriceModel($product->id, 'USD', $prices);
        $option = $this->createOptionModel();
        $option->products()->save($product);

        return [$product, $option];
    }

    protected function createPriceModel(int $related_id, string $currency = 'USD', array $prices = ['monthly' => 10], string $related_type = 'product')
    {
        $price = new Pricing;
        $price->related_id = $related_id;
        $price->related_type = $related_type;
        $price->currency = $currency;
        $price->onetime = $prices['onetime'] ?? null;
        $price->monthly = $prices['monthly'] ?? null;
        $price->quarterly = $prices['quarterly'] ?? null;
        $price->semiannually = $prices['semiannually'] ?? null;
        $price->annually = $prices['annually'] ?? null;
        $price->biennially = $prices['biennially'] ?? null;
        $price->triennially = $prices['triennially'] ?? null;
        $price->setup_onetime = $prices['setup_onetime'] ?? null;
        $price->setup_monthly = $prices['setup_monthly'] ?? null;
        $price->setup_quarterly = $prices['setup_quarterly'] ?? null;
        $price->setup_semiannually = $prices['setup_semiannually'] ?? null;
        $price->setup_annually = $prices['setup_annually'] ?? null;
        $price->setup_biennially = $prices['setup_biennially'] ?? null;
        $price->setup_triennially = $prices['setup_triennially'] ?? null;
        $price->save();

        return $price;
    }

    protected function createCustomerModel()
    {
        return Customer::factory()->create();
    }

    protected function createAdminModel()
    {
        return Admin::factory()->create();
    }

    protected function createServiceModel(int $customer_id, string $status = 'active', array $prices = ['monthly' => 10])
    {
        $service = new \App\Models\Provisioning\Service;
        $service->name = 'Test Service';
        $service->type = 'none';
        $service->currency = 'USD';
        $service->status = $status;
        $service->expires_at = \Carbon\Carbon::now()->addDays(30);
        $service->customer_id = $customer_id;
        $service->save();
        if (! empty($prices)) {
            $this->createPriceModel($service->id, 'USD', $prices, 'service');
        }

        return $service;
    }

    protected function createCouponModel(string $type = 'percent', array $prices = ['monthly' => 10])
    {
        $coupon = new \App\Models\Store\Coupon;
        $coupon->code = 'Test Coupon';
        $coupon->type = $type;
        $coupon->save();
        $this->createPriceModel($coupon->id, 'USD', $prices, 'coupon');

        return $coupon;
    }

    protected function createOptionModel(string $type = 'text', string $key = 'key', array $prices = ['monthly' => 10])
    {
        $option = new \App\Models\Billing\ConfigOption;
        $option->name = 'Test Option';
        $option->key = $key;
        $option->type = $type;
        $option->hidden = 0;
        $option->save();
        if ($type == 'select' || $type == 'radio' || $type == 'checkbox') {
            $this->createOptionValueModel($option->id, ['monthly' => 10]);
        }
        $this->createPriceModel($option->id, 'USD', $prices, 'config_option');

        return $option;
    }

    protected function createOptionValueModel(int $option_id, array $prices = ['monthly' => 10])
    {
        $optionValue = new \App\Models\Billing\ConfigOptionsOption;
        $optionValue->option_id = $option_id;
        $optionValue->value = 'test';
        $optionValue->friendly_name = 'Test Value';
        $optionValue->hidden = false;
        $optionValue->save();
        $this->createPriceModel($optionValue->id, 'USD', $prices, 'config_options_option');

        return $optionValue;
    }

    protected function createBasketForCustomer(Customer $customer)
    {
        $uuid = 'aaaa-aaaa-aaaa-aaaa';
        $basket = Basket::create([
            'user_id' => $customer->id,
            'uuid' => $uuid,
        ]);
        $customer->attachMetadata('basket_uuid', $uuid);

        return $basket;
    }

    protected function createCoupon(array $factory = [], array $pricing = ['monthly' => 10])
    {
        $coupon = Coupon::factory()->create($factory);
        if (! empty($pricing)) {
            $this->createPriceModel($coupon->id, 'USD', $pricing, 'coupon');
        }

        return $coupon;
    }
}
