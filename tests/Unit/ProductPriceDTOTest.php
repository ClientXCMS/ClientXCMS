<?php

namespace Tests\Unit;

use App\DTO\Store\ProductPriceDTO;
use App\Models\Admin\Setting;
use App\Services\Store\TaxesService;
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

    /** Pour la cohérence, on force la TVA à 20 % via réglage fixe. */
    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateSettings([
            'store_vat_enabled' => true,
            'store_mode_tax'    => TaxesService::MODE_TAX_EXCLUDED,
            // Si l’app utilise env('STORE_FIXED_VAT_RATE'), on le fixe via config helper
        ]);

        \Lang::addLines([
            'store.product.freemessage'   => 'Gratuit',
            'store.product.setupmessage' => ':first + setup, puis :recurring chaque :unit :tax',
            'store.product.nocharge'     => ':first chaque :unit :tax',
            'store.ttc'                  => 'TTC',
            'store.ht'                   => 'HT',
        ], 'fr');
        config(['app.locale' => 'fr']); // éviter erreurs de localisation
    }

    public function test_ttc_input_is_converted_to_ht_in_tax_included_mode(): void
    {
        Setting::updateSettings(['store_mode_tax' => TaxesService::MODE_TAX_INCLUDED]);

        $dto = new ProductPriceDTO(
            recurringprice: 120.0, // TTC
            setup: null,
            currency: 'EUR',
            recurring: 'monthly',
        );

        $this->assertSame(100.0, $dto->priceHT());
        $this->assertSame(120.0, $dto->priceTTC());
        $this->assertEquals(20.0, $dto->taxAmount());
    }

    public function test_ht_input_is_kept_as_is_in_tax_excluded_mode(): void
    {
        Setting::updateSettings(['store_mode_tax' => TaxesService::MODE_TAX_EXCLUDED]);

        $dto = new ProductPriceDTO(
            recurringprice: 100.0, // HT
            setup: 10.0,           // HT
            currency: 'EUR',
            recurring: 'monthly',
        );

        $this->assertSame(100.0, $dto->priceHT());
        $this->assertSame(10.0,  $dto->setupHT());
        $this->assertSame(120.0, $dto->priceTTC());     // 100 * 1.20
        $this->assertSame(12.0,  $dto->setupTTC());     // 10  * 1.20
    }

    public function test_free_product_is_detected(): void
    {
        $dto = new ProductPriceDTO(
            recurringprice: 0.0,
            setup: null,
            currency: 'EUR',
            recurring: 'monthly',
        );

        $this->assertTrue($dto->isFree());
        $this->assertSame(0.0, $dto->recurringPayment());
        $this->assertSame(0.0, $dto->billableAmount());
    }

    public function test_onetime_payment_flow(): void
    {
        $dto = new ProductPriceDTO(
            recurringprice: 200.0, // HT
            setup: 50.0,           // HT
            currency: 'EUR',
            recurring: 'onetime',
        );

        $this->assertSame(0.0, $dto->recurringPayment());
        $this->assertSame(200.0, $dto->onetimePayment());
        $this->assertSame(250.0, $dto->billableAmount());
    }

    public function test_billable_amount_includes_firstpayment_and_setup(): void
    {
        $dto = new ProductPriceDTO(
            recurringprice: 30.0,
            setup: 10.0,
            currency: 'EUR',
            recurring: 'monthly',
            firstpayment: 40.0,   // HT diff du récurrent
        );

        $this->assertSame(40.0, $dto->billableAmount()); // firstpayment override is treated as montant facturé
    }


    /* ------------------------------------------------------------------ */
    /*                      Tests sur displayPrice()                      */
    /* ------------------------------------------------------------------ */

    public function testDisplayPriceReturnsTtcWhenConfiguredAndPriceIsHt()
    {
        // Contexte : prix saisi hors taxe (mode TAX_EXCLUDED) mais affichage en TTC.
        Setting::updateSettings([
            'store_mode_tax'         => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price'  => TaxesService::PRICE_TTC,
        ]);

        $dto = new ProductPriceDTO(100.0, null, 'EUR', 'monthly'); // 100 € HT

        // 20 % de TVA → 120 € TTC attendus
        $this->assertEquals(120.0, $dto->displayPrice(), 0.01);
    }

    public function testDisplayPriceReturnsHtWhenConfigured()
    {
        // Contexte : même prix HT mais affichage en HT.
        Setting::updateSettings([
            'store_mode_tax'         => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price'  => TaxesService::PRICE_HT,
        ]);

        $dto = new ProductPriceDTO(100.0, null, 'EUR', 'monthly');

        $this->assertEquals(100.0, $dto->displayPrice(), 0.01);
    }

    public function testDisplayPriceWithTtcInputDoesNotAddVatTwice()
    {
        // Prix saisi en TTC (mode TAX_INCLUDED) et on veut l’afficher TTC :
        Setting::updateSettings([
            'store_mode_tax'         => TaxesService::MODE_TAX_INCLUDED,
            'display_product_price'  => TaxesService::PRICE_TTC,
        ]);

        $dto = new ProductPriceDTO(120.0, null, 'EUR', 'monthly'); // 120 € TTC → ~100 € HT en interne

        // L’affichage TTC doit rester 120 et ne pas passer à 144.
        $this->assertEquals(120.0, $dto->displayPrice(), 0.01);
    }

    public function testCanHydrateWithHtAmountsEvenWhenStoreIsTtc(): void
    {
        Setting::updateSettings([
            'store_mode_tax' => TaxesService::MODE_TAX_INCLUDED,
            'store_vat_enabled' => true,
        ]);

        $dto = new ProductPriceDTO(
            recurringprice: 100.0, // Montant HT fourni directement
            setup: 10.0,
            currency: 'EUR',
            recurring: 'monthly',
            firstpayment: null,
            mode: TaxesService::MODE_TAX_INCLUDED,
            amountsAreHt: true,
        );

        $this->assertSame(100.0, $dto->priceHT());
        $this->assertSame(10.0, $dto->setupHT());
        $this->assertSame(120.0, $dto->priceTTC());
        $this->assertSame(12.0, $dto->setupTTC());
    }

    /* ------------------------------------------------------------------ */
    /*                     Tests sur pricingMessage()                     */
    /* ------------------------------------------------------------------ */

    public function testPricingMessageWithSetupShowsBothAmounts()
    {
        Setting::updateSettings([
            'store_mode_tax'         => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price'  => TaxesService::PRICE_TTC,
        ]);

        // Prix récurrent 100 € HT, setup 10 € HT
        $dto = new ProductPriceDTO(100.0, 10.0, 'EUR', 'monthly');

        $message = $dto->pricingMessage();

        // Le premier paiement TTC devrait inclure setup : 110 € HT → 132 € TTC
        $this->assertStringContainsString('132', $message);
        // Le paiement récurrent TTC : 100 € HT → 120 € TTC
        $this->assertStringContainsString('120', $message);
    }

    public function testPricingMessageWithoutSetupShowsRecurringOnly()
    {
        Setting::updateSettings([
            'store_mode_tax'         => TaxesService::MODE_TAX_EXCLUDED,
            'display_product_price'  => TaxesService::PRICE_TTC,
        ]);

        $dto = new ProductPriceDTO(50.0, null, 'EUR', 'monthly');

        $message = $dto->pricingMessage(false); // pas de setup affiché

        // Doit contenir le prix TTC (50 € HT → 60 € TTC)
        $this->assertStringContainsString('60', $message);
        // Ne doit pas contenir de mention "setup"
        $this->assertStringNotContainsString('setup', $message);
    }
}
