<?php

namespace Tests\Unit\Models\Provisioning;

use App\Models\Provisioning\SubdomainHost;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_unrestricted_subdomain_host_is_available_for_any_product(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        $host = SubdomainHost::create([
            'domain' => 'example.com',
        ]);

        $this->assertTrue($host->isAvailableForProduct($product));
        $this->assertTrue(SubdomainHost::availableForProduct($product)->whereKey($host->id)->exists());
    }

    public function test_subdomain_host_can_be_restricted_to_product(): void
    {
        $allowedProduct = Product::factory()->create(['status' => 'active']);
        $otherProduct = Product::factory()->create(['status' => 'active']);
        $host = SubdomainHost::create([
            'domain' => 'product.test',
            'products' => [$allowedProduct->id],
        ]);

        $this->assertTrue($host->isAvailableForProduct($allowedProduct));
        $this->assertFalse($host->isAvailableForProduct($otherProduct));
        $this->assertTrue(SubdomainHost::availableForProduct($allowedProduct)->whereKey($host->id)->exists());
        $this->assertFalse(SubdomainHost::availableForProduct($otherProduct)->whereKey($host->id)->exists());
    }

    public function test_subdomain_host_can_be_restricted_to_group(): void
    {
        $allowedGroup = Group::factory()->create(['status' => 'active']);
        $otherGroup = Group::factory()->create(['status' => 'active']);
        $allowedProduct = Product::factory()->create([
            'group_id' => $allowedGroup->id,
            'status' => 'active',
        ]);
        $otherProduct = Product::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => 'active',
        ]);
        $host = SubdomainHost::create([
            'domain' => 'group.test',
            'groups' => [$allowedGroup->id],
        ]);

        $this->assertTrue($host->isAvailableForProduct($allowedProduct));
        $this->assertFalse($host->isAvailableForProduct($otherProduct));
        $this->assertTrue(SubdomainHost::availableForProduct($allowedProduct)->whereKey($host->id)->exists());
        $this->assertFalse(SubdomainHost::availableForProduct($otherProduct)->whereKey($host->id)->exists());
    }
}
