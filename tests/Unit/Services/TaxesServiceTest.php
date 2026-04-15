<?php

namespace Tests\Unit\Services;

use App\Services\SettingsService;
use App\Services\Store\TaxesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_vat_price(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('store_vat_enabled', true);
        $settings->save();
        $price = 100;
        $vat = 'FR';
        $expected = 20;

        $result = TaxesService::getVatPrice($price, $vat);

        $this->assertEquals($expected, $result);
    }

    public function test_get_vat_price_with_invalid_iso(): void
    {
        $price = 100;
        $expected = 20;

        $result = TaxesService::getVatPrice($price, 'invalid');

        $this->assertEquals($expected, $result);
    }

    public function test_get_price_with_vat(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('store_vat_enabled', true);
        $settings->save();
        $price = 100;
        $vat = 'FR';
        $expected = 120;

        $result = TaxesService::getPriceWithVat($price, $vat);

        $this->assertEquals($expected, $result);
    }
    /**
     * @todo: fix this test when we have a fixed vat rate
     */
    // public function test_get_price_with_vat_fixed(): void
    // {
    //     $settings = app(SettingsService::class);
    //     $settings->set('store_vat_enabled', true);
    //     $settings->save();
    //     $expected = 30;

    //     putenv('STORE_FIXED_VAT_RATE=30');

    //     $result = TaxesService::getVatPercent();
    //     $this->assertEquals($expected, $result);
    // }

    public function test_get_price_with_vat_disabled(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('store_vat_enabled', false);
        $settings->save();
        $expected = 0;

        $result = TaxesService::getVatPercent();

        $this->assertEquals($expected, $result);
    }
}
