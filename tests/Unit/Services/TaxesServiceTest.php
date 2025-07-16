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
namespace Tests\Unit\Services;

use App\Services\SettingsService;
use App\Services\Store\TaxesService;
use Tests\TestCase;

class TaxesServiceTest extends TestCase
{
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
