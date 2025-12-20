<?php

namespace Tests\Feature\Store\Basket;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use App\Models\Store\Product;
use App\Services\SettingsService;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_checkout_get_guest()
    {
        $this->createBasket();
        $this->get(route('front.store.basket.checkout'))->assertOk();
    }

    public function test_checkout_get_logged()
    {
        $user = $this->createCustomerModel();
        $this->createBasket($user);
        $this->actingAs($user)->get(route('front.store.basket.checkout'))->assertOk();
    }

    public function test_checkout_get_mustbeconfirmed()
    {
        app(SettingsService::class)->set('checkout.customermustbeconfirmed', true);
        $user = $this->createCustomerModel();
        $this->createBasket($user);
        $response = $this->actingAs($user)->get(route('front.store.basket.checkout'));
        $response->assertOk();
    }

    public function test_checkout_post_empty_basket()
    {
        $user = $this->createCustomerModel();
        $request = $this->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => $user->zipcode,
            'phone' => $user->phone,
            'region' => $user->region,
            'country' => $user->country,
        ]);

        $request->assertRedirect();
    }

    public function test_checkout_post_guest()
    {
        $request = $this->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
        ]);
        $request->assertRedirect();
        $this->assertGuest();
    }

    public function test_checkout_post_logged_and_unconfirmed()
    {
        $user = $this->createCustomerModel();
        app(SettingsService::class)->set('checkout.customermustbeconfirmed', true);

        $request = $this->actingAs($user)->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => $user->zipcode,
            'phone' => '123456789',
            'region' => $user->region,
            'country' => $user->country,
        ]);
        $this->createBasket();
        $request->assertRedirect();
    }

    public function test_checkout_change_customer_details()
    {
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        $user = $this->createCustomerModel();
        $this->createBasket($user);
        $user->markEmailAsVerified();
        app(SettingsService::class)->set('checkout.toslink', 'https://example.com/tos');
        $request = $this->actingAs($user)->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => 'Roubaix',
            'zipcode' => $user->zipcode,
            'phone' => $user->phone,
            'region' => $user->region,
            'accept_tos' => 'on',
            'country' => $user->country,
        ]);
        $request->assertRedirect();
        $this->assertEquals('Roubaix', $user->fresh()->city);
    }

    public function test_checkout_cannot_pay_because_tos_not_accepted()
    {
        $user = $this->createCustomerModel();
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        $this->createBasket($user);
        $user->markEmailAsVerified();
        Setting::updateSettings(['checkout_toslink' => 'https://example.com/tos']);
        $request = $this->actingAs($user)->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => '59100',
            'phone' => $user->phone,
            'region' => $user->region,
            'country' => 'FR',
        ]);
        $request->assertRedirect();
        $request->assertSessionHasErrors('accept_tos');
    }

    public function test_minimal_amount_for_gateway()
    {
        $product = $this->createProductModel();
        $user = $this->createCustomerModel();
        $this->seed(GatewaySeeder::class);
        $this->actingAs($user)->post(route('front.store.basket.config', $product), ['currency' => 'USD', 'billing' => 'monthly', 'quantity' => 1]);
        // default pricing is 10
        Gateway::where('uuid', 'balance')->update(['minimal_amount' => 15]);
        $response = $this->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => $user->zipcode,
            'region' => $user->region,
            'accept_tos' => 'on',
            'country' => $user->country,
        ]);
        $response->assertSessionHas('error');
        $this->assertEquals(__('store.checkout.minimal_amount', ['amount' => formatted_price(15)]), session('error'));
    }

    public function test_checkout_gateway_not_found()
    {
        $user = $this->createCustomerModel();
        $this->createBasket($user);
        $user->markEmailAsVerified();
        $request = $this->actingAs($user)->post(route('front.store.basket.checkout'), [
            'gateway' => 'notfound',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => $user->zipcode,
            'phone' => $user->phone,
            'region' => $user->region,
            'accept_tos' => 'on',
            'country' => $user->country,
        ]);
        $request->assertRedirect();
        $request->assertSessionHasErrors('gateway');
    }

    public function test_checkout_create_invoice()
    {
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        $user = $this->createCustomerModel();
        $this->createBasket($user);
        $user->markEmailAsVerified();
        Setting::updateSettings(['checkout.toslink' => 'https://example.com/tos']);
        $request = $this->actingAs($user)->post(route('front.store.basket.checkout'), [
            'gateway' => 'balance',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'address' => $user->address,
            'address2' => $user->address2,
            'city' => $user->city,
            'zipcode' => $user->zipcode,
            'phone' => $user->phone,
            'region' => $user->region,
            'accept_tos' => 'on',
            'country' => $user->country,
        ]);
        $request->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'status' => 'pending',
        ]);
        $now = now()->format('d/m/y');
        $expiresAt = now()->addMonths(1)->format('d/m/y');
        $name = 'Test Product (' . $now . ' - '. $expiresAt . ')';
        $invoice = Invoice::orderBy('id', 'DESC')->first();
        $this->assertEquals(12, $invoice->total);
        $this->assertEquals(10, $invoice->subtotal);
        $this->assertEquals(2, $invoice->tax);
        $this->assertEquals(0, $invoice->setupfees);
        $this->assertEquals('eur', $invoice->currency);
        $this->assertEquals('pending', $invoice->status);
        $item = $invoice->items->first();
        $this->assertEquals($name, $item->name);
        $this->assertEquals('Test Product Description', $item->description);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals(10, $item->unit_price_ht);
        $this->assertEquals(0, $item->unit_setup_ht);
        $this->assertEquals(12, $item->unit_price_ttc);

        $this->assertEquals(Product::orderBy('id', 'DESC')->first()->id, $item->related_id);
        $this->assertEquals([], $item->data);
    }

    protected function createBasket(?Customer $customer = null)
    {
        Basket::where('uuid', 'aaaa-aaaa-aaaa-aaaa')->delete();
        $basket = Basket::create([
            'user_id' => $customer?->id,
            'ipaddress' => request()->ip(),
            'uuid' => 'aaaa-aaaa-aaaa-aaaa',
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

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->migrateExtension('socialauth');
    }


    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BasketRow::truncate();
        Basket::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
