<?php

namespace Tests\Unit\Store;

use App\DTO\Store\UpgradeDTO;
use App\DTO\Store\ProductPriceDTO;
use App\Services\Store\TaxesService;
use App\Models\Billing\Upgrade as UpgradeModel;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UpgradeDTOTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax' => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price' => TaxesService::PRICE_TTC,
            'store_vat_enabled' => true,
            'minimum_days_to_force_renewal_with_upgrade' => 3,
            'add_setupfee_on_upgrade' => 'false',
            'store_currency' => 'USD'
        ]);
    }

    public function test_must_force_renewal_returns_true_for_trial(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->trial_ends_at = Carbon::now()->addDays(5);
        $service->save();

        $this->assertTrue(UpgradeDTO::mustForceRenewal($service));
    }

    public function test_must_force_renewal_returns_true_when_few_days_left(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->expires_at = Carbon::now()->addDays(2);
        $service->save();

        $this->assertTrue(UpgradeDTO::mustForceRenewal($service));
    }

    public function test_must_force_renewal_returns_false_when_more_days_left(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->expires_at = Carbon::now()->addDays(10);
        $service->save();

        $this->assertFalse(UpgradeDTO::mustForceRenewal($service));
    }

    /* ------------------------------------------------------------------ */
    /*                    Tests sur generatePrice()                        */
    /* ------------------------------------------------------------------ */

    public function test_generate_price_returns_free_dto_for_trial_service(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);
        $newProduct = $this->createProductModel(prices: ['monthly' => 20]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->trial_ends_at = Carbon::now()->addDays(5);
        $service->save();

        $dto = new UpgradeDTO($service);
        $priceDto = $dto->generatePrice($newProduct);

        $this->assertTrue($priceDto->isFree());
        $this->assertEquals(0.0, $priceDto->billableAmount());
    }

    public function test_generate_price_returns_onetime_price(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['onetime' => 50]);
        $newProduct = $this->createProductModel(prices: ['onetime' => 80]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->currency = 'USD';
        $service->billing = 'onetime';
        $service->save();

        $dto = new UpgradeDTO($service);
        $priceDto = $dto->generatePrice($newProduct);

        $expectedDto = $newProduct->getPriceByCurrency('USD', 'onetime');

        $this->assertEquals($expectedDto->priceHT(), $priceDto->priceHT());
        $this->assertEquals($expectedDto->billableAmount(), $priceDto->billableAmount());
    }

    public function test_generate_price_with_tax_included_pricing_does_not_double_vat(): void
    {
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax' => TaxesService::MODE_TAX_INCLUDED,
            'store_vat_enabled' => true,
        ]);

        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 60]);
        $newProduct = $this->createProductModel(prices: ['monthly' => 120]); // TTC

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->currency = 'USD';
        $service->save();

        $dto = new UpgradeDTO($service);
        $priceDto = $dto->generatePrice($newProduct);

        $this->assertEquals(100.0, $priceDto->priceHT());
        $this->assertEquals(120.0, $priceDto->priceTTC());
    }

    /* ------------------------------------------------------------------ */
    /*                       Tests toInvoiceItem()                         */
    /* ------------------------------------------------------------------ */

    public function test_to_invoice_item_structure(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);
        $newProduct = $this->createProductModel(prices: ['monthly' => 20]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        $service->save();

        $dto = new UpgradeDTO($service);
        $upgradeRecord = $dto->createUpgrade($newProduct);
        $item = $dto->toInvoiceItem($newProduct, $upgradeRecord);

        $this->assertSame('upgrade', $item['type']);
        $this->assertSame($upgradeRecord->id, $item['related_id']);
        $this->assertArrayHasKey('unit_price_ttc', $item);
        $this->assertArrayHasKey('unit_price_ht', $item);
        $this->assertArrayHasKey('unit_setup_ht', $item);
        $this->assertIsNumeric($item['unit_price_ttc']);
        $upgradeRecord->delete();
    }


    /* ------------------------------------------------------------------ */
    /*                Aide pour créer les services/produits               */
    /* ------------------------------------------------------------------ */

    private function prepareService(string $billing = 'monthly', int $daysLeft = 15, array $oldPrices = ['monthly' => 10], array $newPrices = ['monthly' => 20]): array
    {
        $customer  = $this->createCustomerModel();
        $oldProduct = $this->createProductModel('active', 1, $oldPrices); // Ex : 10 $ / mois
        $newProduct = $this->createProductModel('active', 1, $newPrices); // Ex : 20 $ / mois

        // Crée le service existant rattaché à l’ancien produit
        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing    = $billing;         // weekly, monthly, onetime…
        $service->currency   = 'USD';
        $service->expires_at = Carbon::now()->addDays($daysLeft);
        $service->save();

        return [$service, $newProduct, $oldProduct];
    }

    /* ------------------------------------------------------------------ */
    /*                          Tests prorata HT                          */
    /* ------------------------------------------------------------------ */

    public function test_generate_price_prorata_mid_month()
    {
        [$service, $newProduct] = $this->prepareService('monthly', 15);

        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        $now = new \DateTime();
        $daysInMonth = $now->format('t');
        $diff = $now->diff($service->expires_at);
        $diffInDays = $diff->d;
        $daysInMonth = (int) $now->format('t');
        $priceDiff   = 20 - 10;
        $expectedProrata = round($diffInDays / $daysInMonth * $priceDiff, 2);
        $this->assertEquals($expectedProrata, $dto->firstPaymentHT(), 0.01);
        $this->assertEquals(20, $dto->priceHT());
        $this->assertSame('monthly', $dto->recurring);
    }

    public function test_generate_price_force_renewal_when_few_days()
    {
        [$service, $newProduct] = $this->prepareService('monthly', 2); // ≤ threshold → renouvellement

        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);
        $now = new \DateTime();
        $diff = $now->diff($service->expires_at);
        $diffInDays = $diff->d;
        $daysInMonth = (int) $now->format('t');
        $priceDiff   = 20 - 10;
        $prorata     = round($diffInDays / $daysInMonth * $priceDiff, 2);
        $expectedFirst = $prorata + 20;  // Ajout du mois complet
        $this->assertEquals($expectedFirst, $dto->firstPaymentHT(), 0.01);
    }
    public function test_generate_price_includes_setup_fee_when_enabled()
    {
        \App\Models\Admin\Setting::updateSettings(['add_setupfee_on_upgrade' => 'true']);
        [$service, $newProduct] = $this->prepareService('monthly', 10, ['monthly' => 10], ['monthly' => 0, 'setup_monthly' => 5]);
        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        $this->assertEquals(5, $dto->setupHT());
    }

    public function test_weekly_billing_returns_monthly_price_without_weekly_billing()
    {
        [$service, $newProduct] = $this->prepareService('weekly', 10);
        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);
        $this->assertEquals(20, $dto->priceHT());
        $this->assertSame('monthly', $dto->recurring);
    }

    public function test_weekly_billing_returns_weekly_price_with_weekly_billing()
    {
        [$service, $newProduct] = $this->prepareService('weekly', 10, ['weekly' => 5], ['weekly' => 15]);
        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);
        $this->assertEquals(15, $dto->priceHT());
        $this->assertSame('weekly', $dto->recurring);
    }

}
