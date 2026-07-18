<?php

namespace Tests\Feature\Store;

use App\Core\Domain\FakeDomainRegistrar;
use App\Models\Store\Basket\Basket;
use App\Models\Store\DomainTld;
use App\Models\Store\DomainTldPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session()->flush();
        session()->start();

        Basket::$basket = null;
        session()->put('basket_uuid', 'domain-basket');
        Basket::$basket = Basket::create([
            'user_id' => null,
            'uuid' => 'domain-basket',
            'completed_at' => null,
        ]);
    }

    public function test_fake_registrar_checks_availability_and_manages_dns(): void
    {
        $registrar = new FakeDomainRegistrar;
        $customer = $this->createCustomerModel();
        $service = $this->createServiceModel($customer->id, 'active', []);
        $service->type = 'domain';
        $service->name = 'example.com';
        $service->data = ['domain' => 'example.com'];
        $service->save();

        $this->assertTrue($registrar->checkAvailability('example.com')->available);
        $this->assertFalse($registrar->checkAvailability('taken.com')->available);

        $registrar->createDnsRecord($service, ['type' => 'A', 'name' => '@', 'value' => '127.0.0.1']);

        $this->assertCount(1, $registrar->getDnsRecords($service));
    }

    public function test_basket_config_rejects_unknown_tld(): void
    {
        // D1: a known TLD with valid pricing is the only acceptable input.
        // Without this, an attacker can hand a TLD label that ends up in the
        // basket row data and shifts the unit price away from the catalog
        // (priceFor() falls back, opening a pricing-manipulation window).
        $product = $this->createProductModel('active', 10, []);
        $product->type = 'domain';
        $product->save();

        $response = $this->post(route('front.store.basket.config', $product), [
            'currency' => 'USD',
            'billing' => 'annually',
            'domain' => 'example.com',
            'tld' => '.does-not-exist',
        ]);

        // BasketConfigRequest surfaces validation failure via a back()
        // redirect with a flash 'error' instead of the standard errors
        // bag - pre-existing pattern in BasketController::configProduct.
        $response->assertSessionHas('error');
        $this->assertSame(0, Basket::getBasket()->rows()->count(), 'No row must land in the basket on rejected input');
    }

    public function test_domain_product_uses_tld_pricing_in_basket(): void
    {
        $product = $this->createProductModel('active', 10, []);
        $product->type = 'domain';
        $product->save();

        $tld = DomainTld::create(['extension' => '.com', 'status' => 'active']);
        DomainTldPrice::create([
            'domain_tld_id' => $tld->id,
            'currency' => 'USD',
            'action' => 'register',
            'billing' => 'annually',
            'price' => 10,
            'setup' => 0,
        ]);

        $response = $this->post(route('front.store.basket.config', $product), [
            'currency' => 'USD',
            'billing' => 'annually',
            'domain' => 'example.com',
            'tld' => '.com',
        ]);

        $response->assertRedirect(route('front.store.basket.show'));
        $row = Basket::getBasket()->rows()->first();

        $this->assertSame('example.com', $row->data['domain']);
        $this->assertSame('.com', $row->data['tld']);
        $this->assertEquals(10, $row->recurringPayment(false));
    }
}
