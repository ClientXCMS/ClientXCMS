<?php

namespace Tests\Unit\Services\Billing;

use App\Contracts\Billing\AccountingExportProviderInterface;
use App\Services\Billing\AccountingProviderRegistry;
use Mockery;
use PHPUnit\Framework\TestCase;

class AccountingProviderRegistryTest extends TestCase
{
    public function test_it_returns_only_enabled_accounting_destinations(): void
    {
        $enabled = Mockery::mock(AccountingExportProviderInterface::class);
        $enabled->allows(['key' => 'enabled', 'enabled' => true]);
        $disabled = Mockery::mock(AccountingExportProviderInterface::class);
        $disabled->allows(['key' => 'disabled', 'enabled' => false]);
        $registry = new AccountingProviderRegistry;
        $registry->register($enabled);
        $registry->register($disabled);
        $this->assertSame(['enabled'], array_keys($registry->enabled()));
        $this->assertSame($disabled, $registry->get('disabled'));
        Mockery::close();
    }
}
