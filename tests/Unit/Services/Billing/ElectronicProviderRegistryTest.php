<?php

namespace Tests\Unit\Services\Billing;

use App\Services\Billing\ElectronicProviderRegistry;
use App\Services\Billing\LocalElectronicExchangeProvider;
use PHPUnit\Framework\TestCase;

class ElectronicProviderRegistryTest extends TestCase
{
    public function test_provider_is_registered_by_stable_key(): void
    {
        $registry = new ElectronicProviderRegistry;
        $provider = new LocalElectronicExchangeProvider;
        $registry->register($provider);
        $this->assertSame($provider, $registry->get('local'));
        $this->assertContains('simulation', $provider->capabilities());
        $this->assertTrue($registry->has('local'));
        $this->assertTrue($registry->supports('local', 'b2b'));
        $this->assertFalse($registry->supports('local', 'unknown'));
    }
}
