<?php

namespace Tests\Unit;

use App\DTO\Store\ProductPriceDTO;
use App\Exceptions\WrongPaymentException;
use App\Models\Provisioning\Service;
use App\Services\Domain\DomainPricingService;
use Mockery;
use Tests\TestCase;

class DomainServiceRenewalPricingTest extends TestCase
{
    private function service(): Service
    {
        return new Service([
            'type' => 'domain', 'name' => 'example.fr', 'currency' => 'EUR',
            'billing' => 'annually', 'product_id' => 12, 'data' => ['tld' => '.fr'],
        ]);
    }

    private function prices(array $prices): void
    {
        $provider = Mockery::mock(DomainPricingService::class);
        $provider->shouldReceive('availableForTld')->with('.fr', 'EUR', DomainPricingService::ACTION_RENEW)->andReturn($prices);
        $this->app->instance(DomainPricingService::class, $provider);
    }

    public function test_domain_uses_tld_renewal_price_even_with_a_product(): void
    {
        $price = new ProductPriceDTO(25, 0, 'EUR', 'annually');
        $this->prices([$price]);
        $service = $this->service();
        $this->assertSame($price, $service->getBillingPrice());
        $this->assertSame($price, $service->getPriceByCurrency('EUR', 'annually'));
        $this->assertSame($price->base_price, $service->getBillingPrice()->base_price);
    }

    public function test_only_configured_one_two_three_year_periods_are_available(): void
    {
        $this->prices([
            new ProductPriceDTO(70, 0, 'EUR', 'triennially'),
            new ProductPriceDTO(2, 0, 'EUR', 'monthly'),
            new ProductPriceDTO(25, 0, 'EUR', 'annually'),
        ]);
        $service = $this->service();
        $this->assertSame(['annually', 'triennially'], array_column($service->pricingAvailable(), 'recurring'));
        $this->assertTrue($service->hasBilling('annually'));
        $this->assertFalse($service->hasBilling('biennially'));
        $this->assertFalse($service->hasBilling('monthly'));
    }

    public function test_two_year_renewal_uses_its_own_total(): void
    {
        $price = new ProductPriceDTO(43, 0, 'EUR', 'biennially');
        $this->prices([new ProductPriceDTO(25, 0, 'EUR', 'annually'), $price]);
        $this->assertSame($price, $this->service()->getBillingPrice('biennially'));
    }

    public function test_missing_tariff_never_becomes_a_free_renewal(): void
    {
        $this->prices([]);
        $service = $this->service();
        $this->assertFalse($service->hasPricesForCurrency('EUR'));
        $this->expectException(WrongPaymentException::class);
        $service->getBillingPrice();
    }

    public function test_unconfigured_period_is_rejected(): void
    {
        $this->prices([new ProductPriceDTO(25, 0, 'EUR', 'annually')]);
        $this->expectException(WrongPaymentException::class);
        $this->service()->getBillingPrice('monthly');
    }

    public function test_invoice_creation_rejects_invalid_domain_period_before_persisting(): void
    {
        $this->prices([new ProductPriceDTO(25, 0, 'EUR', 'annually')]);
        $this->expectException(WrongPaymentException::class);
        \App\Services\Billing\InvoiceService::createInvoiceFromService($this->service(), 'monthly');
    }

    public function test_currency_is_not_taken_from_another_tld_tariff(): void
    {
        $provider = Mockery::mock(DomainPricingService::class);
        $provider->shouldReceive('availableForTld')->once()->with('.fr', 'USD', DomainPricingService::ACTION_RENEW)->andReturn([]);
        $this->app->instance(DomainPricingService::class, $provider);
        $this->expectException(WrongPaymentException::class);
        $this->service()->getPriceByCurrency('USD', 'annually');
    }
}
