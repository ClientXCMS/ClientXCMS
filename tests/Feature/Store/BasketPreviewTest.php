<?php

namespace Tests\Feature\Store;

use App\Services\Store\TaxesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasketPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_endpoint_returns_totals_and_options_breakdown(): void
    {
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax'    => TaxesService::MODE_TAX_EXCLUDED,
            'store_vat_enabled' => true,
        ]);

        [$product, $option] = $this->createProductModelWithOption(prices: ['monthly' => 100, 'setup_monthly' => 10]);

        $response = $this->postJson(route('front.store.basket.config.preview', $product), [
            'billing'  => 'monthly',
            'currency' => 'USD',
            'options'  => [
                $option->key => 'value',
            ],
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(120.0, $data['totals']['first_payment_ht']); // 100 + 10 (setup) + 10 option
        $this->assertEquals(24.0, $data['totals']['tax']);              // 20 % de TVA
        $this->assertEquals(144.0, $data['totals']['total']);

        $this->assertCount(1, $data['options']);
        $this->assertSame($option->key, $data['options'][0]['key']);
        $this->assertEquals(10.0, $data['options'][0]['amount_ht']);
    }

    public function test_preview_endpoint_handles_tax_included_mode(): void
    {
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax'    => TaxesService::MODE_TAX_INCLUDED,
            'store_vat_enabled' => true,
        ]);

        [$product] = $this->createProductModelWithOption(prices: ['monthly' => 120]);

        $response = $this->postJson(route('front.store.basket.config.preview', $product), [
            'billing'  => 'monthly',
            'currency' => 'USD',
            'options'  => [],
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(100.0, $data['totals']['first_payment_ht']); // 120 TTC => 100 HT
        $this->assertEquals(20.0, $data['totals']['tax']);
        $this->assertEquals(120.0, $data['totals']['total']);
    }
}
