<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Billing\ElectronicDocument;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../../addons/chorus-pro/src/ChorusProExchangeProvider.php';

class ChorusProExchangeProviderTest extends TestCase
{
    public function test_it_normalizes_chorus_lifecycle_statuses(): void
    {
        $provider = new \App\Addons\ChorusPro\ChorusProExchangeProvider;

        $this->assertSame(ElectronicDocument::STATUS_REJECTED, $provider->normalizeStatus('REJETE'));
        $this->assertSame(ElectronicDocument::STATUS_DELIVERED, $provider->normalizeStatus('INTEGRE'));
        $this->assertSame(ElectronicDocument::STATUS_DELIVERED, $provider->normalizeStatus('MANDATE'));
        $this->assertSame(ElectronicDocument::STATUS_SUBMITTED, $provider->normalizeStatus('DEPOSE'));
    }
}
