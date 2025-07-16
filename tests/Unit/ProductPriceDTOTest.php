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
namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceDTOTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_start_with_empty_prices()
    {
        app(\App\Services\Store\CurrencyService::class)->setCurrency('USD');
        $group = $this->createGroupModel();
        $this->assertEquals(0, $group->startPrice()->price);
    }

    public function test_product_start_price_dto()
    {
        app(\App\Services\Store\CurrencyService::class)->setCurrency('USD');
        $group = $this->createGroupModel();
        for ($i = 1; $i <= 10; $i++) {
            $product = $this->createProductModel('active', 1, ['monthly' => $i * 10, 'triennially' => $i * 30]);
            $group->products()->save($product);
        }
        $this->assertEquals(10, $group->startPrice()->price);
        $this->assertEquals('USD', $group->startPrice()->currency);
        $this->assertEquals('monthly', $group->startPrice()->recurring);
        $this->assertEquals(10, $group->startPrice('monthly')->price);
        $this->assertEquals(30, $group->startPrice('triennially')->price);
    }

    public function test_product_start_price_dto_with_only_triennally()
    {
        app(\App\Services\Store\CurrencyService::class)->setCurrency('USD');
        $group = $this->createGroupModel();
        for ($i = 1; $i <= 10; $i++) {
            $product = $this->createProductModel('active', 1, ['triennially' => $i * 30]);
            $group->products()->save($product);
        }
        $this->assertEquals(30, $group->startPrice()->price);
        $this->assertEquals('USD', $group->startPrice()->currency);
        $this->assertEquals('triennially', $group->startPrice()->recurring);
    }

    public function test_product_start_price_dto_with_subgroups()
    {
        app(\App\Services\Store\CurrencyService::class)->setCurrency('USD');
        $group = $this->createGroupModel();
        $subGroup = $this->createGroupModel();
        $subGroup2 = $this->createGroupModel();
        $group->groups()->save($subGroup);
        $group->groups()->save($subGroup2);
        for ($i = 1; $i <= 10; $i++) {
            $product = $this->createProductModel('active', 1, ['monthly' => $i * 10, 'triennially' => $i * 30]);
            $subGroup->products()->save($product);
        }
        for ($i = 1; $i <= 10; $i++) {
            $product = $this->createProductModel('active', 1, ['monthly' => $i * 5, 'triennially' => $i * 15]);
            $subGroup2->products()->save($product);
        }

        $this->assertEquals(5, $group->startPrice()->price);
        $this->assertEquals(10, $subGroup->startPrice()->price);
        $this->assertEquals(5, $subGroup2->startPrice()->price);
        $this->assertEquals('USD', $group->startPrice()->currency);
        $this->assertEquals('monthly', $group->startPrice()->recurring);
        $this->assertEquals(5, $group->startPrice('monthly')->price);
        $this->assertEquals(15, $group->startPrice('triennially')->price);
    }
}
