<?php

namespace Tests\Unit\Store;

use App\DTO\Store\UpgradeDTO;
use App\DTO\Store\ProductPriceDTO;
use App\Services\Store\TaxesService;
use App\Models\Billing\Upgrade as UpgradeModel;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Setting;
use Tests\TestCase;
use Carbon\Carbon;

class UpgradeDTOTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Réglages par défaut pour les tests
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax' => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price' => TaxesService::PRICE_TTC,
            'store_vat_enabled' => true,
            'minimum_days_to_force_renewal_with_upgrade' => 3,
            'add_setupfee_on_upgrade' => 'false',
            'store_currency' => 'USD'
        ]);

        // Traductions minimales pour passer les asserts sur les messages
        Lang::addLines([
            'client.services.upgrade.upgrade_to2' => 'Upgrade vers :product',
        ], Lang::getLocale(), '*');
    }

    /* ------------------------------------------------------------------ */
    /*                     Tests sur mustForceRenewal()                    */
    /* ------------------------------------------------------------------ */

    public function testMustForceRenewalReturnsTrueForTrial(): void
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

    public function testMustForceRenewalReturnsTrueWhenFewDaysLeft(): void
    {
        $customer = $this->createCustomerModel();
        $oldProduct = $this->createProductModel(prices: ['monthly' => 10]);

        $service = $this->createServiceModel($customer->id);
        $service->product_id = $oldProduct->id;
        $service->billing = 'monthly';
        // Expire dans 2 jours -> inférieur au seuil (3)
        $service->expires_at = Carbon::now()->addDays(2);
        $service->save();

        $this->assertTrue(UpgradeDTO::mustForceRenewal($service));
    }

    public function testMustForceRenewalReturnsFalseWhenMoreDaysLeft(): void
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

    public function testGeneratePriceReturnsFreeDtoForTrialService(): void
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

    public function testGeneratePriceReturnsOnetimePrice(): void
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

    /* ------------------------------------------------------------------ */
    /*                       Tests toInvoiceItem()                         */
    /* ------------------------------------------------------------------ */

    public function testToInvoiceItemStructure(): void
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

    public function testGeneratePriceProrataMidMonth()
    {
        [$service, $newProduct] = $this->prepareService('monthly', 15);

        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        $daysInMonth = (int) (new \DateTime)->format('t');
        $priceDiff   = 20 - 10;           // New – Old
        $expectedProrata = round(15 / $daysInMonth * $priceDiff, 2);

        $this->assertEquals($expectedProrata, $dto->firstPaymentHT(), 0.01);
        $this->assertEquals(20, $dto->priceHT());
        $this->assertSame('monthly', $dto->recurring);
    }

    public function testGeneratePriceForceRenewalWhenFewDays()
    {
        [$service, $newProduct] = $this->prepareService('monthly', 2); // ≤ threshold → renouvellement

        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        $daysInMonth = (int) (new \DateTime)->format('t');
        $priceDiff   = 20 - 10;
        $prorata     = round(2 / $daysInMonth * $priceDiff, 2);
        $expectedFirst = $prorata + 20;  // Ajout du mois complet

        $this->assertEquals($expectedFirst, $dto->firstPaymentHT(), 0.01);
    }

    /* ------------------------------------------------------------------ */
    /*                   Setup fee appliqué ou non appliqué               */
    /* ------------------------------------------------------------------ */

    public function testGeneratePriceIncludesSetupFeeWhenEnabled()
    {
        \App\Models\Admin\Setting::updateSettings(['add_setupfee_on_upgrade' => 'true']);

        // Nouveau produit à 20 $ + 5 $ de setup
        [$service, $newProduct] = $this->prepareService('monthly', 10, ['monthly' => 10], ['monthly' => 0, 'setup_monthly' => 5]);
        // Ajoute un setup fee au nouveau produit
        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        $this->assertEquals(5, $dto->setupHT());
    }

    /* ------------------------------------------------------------------ */
    /*                Cas spécifique : billing « weekly » → monthly       */
    /* ------------------------------------------------------------------ */

    public function testWeeklyBillingReturnsMonthlyPrice()
    {
        [$service, $newProduct] = $this->prepareService('weekly', 10);

        $dto = (new UpgradeDTO($service))->generatePrice($newProduct);

        // generatePrice doit renvoyer le prix mensuel du nouveau produit
        $this->assertEquals(20, $dto->priceHT());
        $this->assertSame('monthly', $dto->recurring);
    }

}
