<?php

namespace Tests\Unit\Services\Billing;

use App\Services\Billing\ElectronicProviderRegistry;
use App\Services\Billing\ElectronicProviderResolver;
use App\Services\Billing\LocalElectronicExchangeProvider;
use PHPUnit\Framework\TestCase;

class ElectronicProviderResolverTest extends TestCase
{
    public function test_public_document_uses_the_configured_b2g_provider(): void
    {
        $registry = new ElectronicProviderRegistry;
        $registry->register(new LocalElectronicExchangeProvider);
        $registry->register(new class extends LocalElectronicExchangeProvider
        {
            public function key(): string
            {
                return 'chorus-pro';
            }

            public function capabilities(): array
            {
                return ['einvoicing', 'b2g', 'status_tracking'];
            }
        });
        $this->assertSame('chorus-pro', (new ElectronicProviderResolver($registry))->resolve('local', 'chorus-pro', true));
    }

    public function test_private_document_uses_the_main_provider(): void
    {
        $registry = new ElectronicProviderRegistry;
        $registry->register(new LocalElectronicExchangeProvider);
        $this->assertSame('local', (new ElectronicProviderResolver($registry))->resolve('local', 'chorus-pro', false));
    }

    public function test_missing_or_incompatible_provider_requires_manual_review(): void
    {
        $registry = new ElectronicProviderRegistry;
        $registry->register(new LocalElectronicExchangeProvider);
        $this->assertNull((new ElectronicProviderResolver($registry))->resolve('local', 'missing', true));
    }
}
