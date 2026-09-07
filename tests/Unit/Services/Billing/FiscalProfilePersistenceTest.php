<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Services\Billing\FiscalProfileService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FiscalProfilePersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->default('Test');
            $table->string('lastname')->default('Customer');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('dark_mode')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('locale')->default('fr_FR');
            $table->boolean('gdpr_compliment')->default(false);
            $table->string('customer_type')->default('individual');
            $table->string('tax_subject_status')->default('unknown');
            $table->boolean('fiscal_profile_completed')->default(false);
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('siren', 9)->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('vat_number')->nullable();
            $table->string('tax_registration_number')->nullable();
            $table->string('rna_number', 10)->nullable();
            $table->string('address')->default('1 rue du Test');
            $table->string('address2')->nullable();
            $table->string('zipcode')->default('75001');
            $table->string('city')->default('Paris');
            $table->string('region')->default('Île-de-France');
            $table->string('country')->default('FR');
            $table->string('billing_details')->nullable();
            $table->timestamps();
        });
    }

    public function test_legacy_company_name_is_synchronized_to_the_canonical_name(): void
    {
        $customer = Customer::create(['company_name' => 'Legacy SAS', 'siren' => '123456789']);
        $this->assertSame('Legacy SAS', $customer->legal_name);
        $this->assertSame(Customer::TYPE_BUSINESS, $customer->customer_type);
        $this->assertTrue($customer->fiscal_profile_completed);
    }

    public function test_legal_name_wins_and_the_complete_profile_is_saved_atomically(): void
    {
        $customer = Customer::create();
        $customer = (new FiscalProfileService)->update($customer, [
            'customer_type' => 'business', 'legal_name' => 'Canonical SAS', 'company_name' => 'Legacy value',
            'siren' => '123456789', 'address' => '2 rue du Test', 'zipcode' => '69001',
            'city' => 'Lyon', 'region' => 'Auvergne-Rhône-Alpes', 'country' => 'FR',
            'billing_details' => 'Référence client 42',
        ]);
        $this->assertSame('Canonical SAS', $customer->legal_name);
        $this->assertSame('Canonical SAS', $customer->company_name);
        $this->assertSame('Référence client 42', $customer->billing_details);
        $this->assertTrue($customer->fiscal_profile_completed);
    }

    public function test_additional_billing_details_are_frozen_in_the_snapshot(): void
    {
        $customer = Customer::create(['billing_details' => 'Purchase order 42']);
        $invoice = new Invoice(['currency' => 'EUR', 'paymethod' => 'stripe']);
        $invoice->setRelation('customer', $customer);
        $invoice->billing_address = $customer->generateBillingAddress();

        $snapshot = (new FiscalProfileService)->snapshot($invoice);
        $customer->billing_details = 'Changed later';

        $this->assertSame('Purchase order 42', $snapshot['buyer']['additional_details']);
    }

    public function test_association_qualification_is_persisted_and_frozen_in_the_snapshot(): void
    {
        $customer = Customer::create();
        $customer = (new FiscalProfileService)->update($customer, [
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'tax_subject_status' => Customer::TAX_STATUS_NON_TAXABLE,
            'legal_name' => 'Association Exemple',
            'rna_number' => 'W123456789',
            'address' => '1 rue du Test',
            'zipcode' => '75001',
            'city' => 'Paris',
            'region' => 'Île-de-France',
            'country' => 'FR',
        ]);
        $invoice = new Invoice(['currency' => 'EUR', 'paymethod' => 'stripe']);
        $invoice->setRelation('customer', $customer);
        $invoice->billing_address = $customer->generateBillingAddress();

        $snapshot = (new FiscalProfileService)->snapshot($invoice);

        $this->assertTrue($customer->fiscal_profile_completed);
        $this->assertSame('W123456789', $snapshot['buyer']['rna_number']);
        $this->assertSame(Customer::TAX_STATUS_NON_TAXABLE, $snapshot['buyer']['tax_subject_status']);
        $this->assertSame(FiscalProfileService::ROUTING_EREPORTING, $snapshot['tax']['electronic_routing']);
    }

    public function test_pdf_fiscal_parties_prefer_the_frozen_snapshot(): void
    {
        $customer = Customer::create([
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'legal_name' => 'Nom actuel',
            'address' => 'Adresse actuelle',
        ]);
        $invoice = new Invoice(['currency' => 'EUR', 'paymethod' => 'stripe']);
        $invoice->setRelation('customer', $customer);
        $invoice->billing_address = $customer->generateBillingAddress();
        $invoice->billing_snapshot = [
            'seller' => ['legal_name' => 'Vendeur figé', 'address' => '10 rue du Vendeur', 'siren' => '123456789'],
            'buyer' => [
                'type' => Customer::TYPE_ASSOCIATION,
                'legal_name' => 'Association figée',
                'rna_number' => 'W123456789',
                'address' => ['address' => '20 rue Figée', 'zipcode' => '59000', 'city' => 'Lille', 'country' => 'FR'],
            ],
        ];

        $parties = $invoice->fiscalPartiesForPdf();

        $this->assertSame('Vendeur figé', $parties['seller']['legal_name']);
        $this->assertSame('Association figée', $parties['buyer']['legal_name']);
        $this->assertSame('W123456789', $parties['buyer']['rna_number']);
        $this->assertSame('20 rue Figée', $parties['buyer']['address']);
    }
}
