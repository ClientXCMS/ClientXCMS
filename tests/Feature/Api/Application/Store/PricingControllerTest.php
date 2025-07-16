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
namespace Tests\Feature\Api\Application\Store;

use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingControllerTest extends TestCase
{
    const API_URL = 'api/application/pricings';

    const ABILITY_INDEX = 'pricings:index';

    const ABILITY_STORE = 'pricings:store';

    const ABILITY_SHOW = 'pricings:show';

    const ABILITY_UPDATE = 'pricings:update';

    const ABILITY_DELETE = 'pricings:delete';

    use RefreshDatabase;

    public function test_api_application_pricing_index(): void
    {
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_pricing_filter(): void
    {
        $id = $this->createPricing();
        $response = $this->performAction('GET', self::API_URL.'?filter[related_id]='.$id, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_api_application_pricing_filter_with_related_type(): void
    {
        $id = $this->createPricing();
        $response = $this->performAction('GET', self::API_URL.'?filter[related_id]='.$id.'&filter[related_type]=product', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_api_application_pricing_sort(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createPricing(null, ['monthly' => $i, 'currency' => 'USD']);
        }
        $lastPricing = Pricing::orderBy('monthly', 'desc')->first();
        $response = $this->performAction('GET', self::API_URL.'?sort=-monthly', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals($lastPricing->monthly, $response->json('data.0.monthly'));
    }

    public function test_api_application_pricing_search_by_currency_and_pricing(): void
    {
        $product = $this->createProduct();
        for ($i = 0; $i < 10; $i++) {
            if ($i % 2 == 0) {
                $this->createPricing($product, ['onetime' => 99.99, 'monthly' => 3.99, 'quarterly' => 9.99, 'currency' => 'USD']);
            } else {
                $this->createPricing($product, ['currency' => 'EUR']);
            }
        }

        for ($i = 0; $i < 5; $i++) {
            $this->createPricing($product, ['currency' => 'USD']);
        }
        $response = $this->performAction('GET', self::API_URL.'?filter[monthly]=3.99&currency=EUR', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $response->assertJsonIsArray('data');
        $this->assertCount(5, $response->json('data'));
    }

    public function test_api_application_pricing_search_by_currency(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createPricing(null, ['currency' => 'EUR']);
        }
        $response = $this->performAction('GET', self::API_URL.'?filter[currency]=EUR', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $response->assertJsonIsArray('data');
    }

    public function test_api_application_pricing_store(): void
    {
        $product = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'related_id' => $product->id,
            'onetime' => 99.99,
            'monthly' => 4.99,
            'quarterly' => 9.99,
            'currency' => 'USD',
            'related_type' => 'product',
        ]);
        $response->assertStatus(201);
        $response->assertJsonFragment(['onetime' => 99.99, 'monthly' => 4.99, 'quarterly' => 9.99]);
    }

    public function test_api_application_pricing_invalid_price(): void
    {
        $product = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'related_id' => $product->id,
            'related_type' => 'product',
            'onetime' => 99.99,
            'monthly' => 4.99,
            'quarterly' => -9.99,
            'currency' => 'USD',
        ]);
        $response->assertStatus(422);
    }

    public function test_api_application_pricing_invalid_currency(): void
    {
        $product = $this->createProduct();
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'related_id' => $product->id,
            'onetime' => 99.99,
            'monthly' => 4.99,
            'quarterly' => -9.99,
            'currency' => 'FAKE',
            'related_type' => 'product',
        ]);
        $response->assertStatus(422);
    }

    public function test_api_application_pricing_get(): void
    {
        $id = $this->createPricing();
        $response = $this->performAction('GET', self::API_URL.'/'.$id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_pricing_delete(): void
    {
        $id = $this->createPricing();
        $response = $this->performAction('DELETE', self::API_URL.'/'.$id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);
    }

    public function test_api_application_pricing_update(): void
    {
        $product = $this->createProduct();
        $id = $this->createPricing($product);
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'currency' => 'EUR',
            'monthly' => 14.99,
            'related_id' => $product->id,
            'related_type' => 'product',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['currency' => 'EUR', 'monthly' => 14.99]);
    }

    public function test_api_application_update_pricing_invalid(): void
    {
        $product = $this->createProduct();
        $id = $this->createPricing($product);
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'related_id' => $product->id,
            'currency' => 'EUR',
            'monthly' => -14.99,
            'related_type' => 'product',
        ]);
        $response->assertStatus(422);
    }

    private function createPricing(?Product $product = null, array $data = ['onetime' => 99.99, 'monthly' => 4.99, 'quarterly' => 9.99, 'currency' => 'USD'])
    {
        if ($product == null) {
            $product = $this->createProduct();
        }

        return Pricing::create(array_merge(['related_id' => $product->id, 'related_type' => 'product'], $data))->id;
    }

    private function createProduct()
    {
        if (\App\Models\Store\Group::count() == 0) {
            $group = \App\Models\Store\Group::create([
                'name' => 'Test Group',
                'description' => 'Test Group',
                'slug' => 'test-group',
                'status' => 'active',
            ]);
        } else {
            $group = \App\Models\Store\Group::first();
        }

        return Product::create([
            'name' => 'Test pricing',
            'description' => 'Test pricing',
            'status' => 'active',
            'type' => 'none',
            'stock' => '10',
            'group_id' => $group->id,
            'pinned' => false,
        ]);
    }
}
