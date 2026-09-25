<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Account\Customer;
use App\Services\Billing\FiscalProfileService;
use PHPUnit\Framework\TestCase;

class FiscalProfileServiceTest extends TestCase
{
    public function test_individual_profile_is_complete_without_company_identifiers(): void
    {
        $customer = new Customer(['customer_type' => Customer::TYPE_INDIVIDUAL, 'country' => 'FR']);
        $this->assertTrue((new FiscalProfileService)->isComplete($customer));
    }

    public function test_french_business_requires_legal_name_and_valid_siren(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer(['customer_type' => Customer::TYPE_BUSINESS, 'country' => 'FR', 'legal_name' => 'Example SAS']);
        $this->assertFalse($service->isComplete($customer));
        $customer->siren = '123456789';
        $this->assertTrue($service->isComplete($customer));
    }

    public function test_foreign_business_requires_a_tax_or_vat_identifier(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer(['customer_type' => Customer::TYPE_BUSINESS, 'country' => 'BE', 'legal_name' => 'Example SRL']);
        $this->assertFalse($service->isComplete($customer));
        $customer->vat_number = 'BE0123456789';
        $this->assertTrue($service->isComplete($customer));
    }

    public function test_non_taxable_association_does_not_require_a_siren(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer([
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'tax_subject_status' => Customer::TAX_STATUS_NON_TAXABLE,
            'country' => 'FR',
            'legal_name' => 'Association Exemple',
            'address' => '1 rue du Test', 'zipcode' => '75001', 'city' => 'Paris',
        ]);

        $this->assertTrue($service->isComplete($customer));
        $this->assertSame(FiscalProfileService::ROUTING_EREPORTING, $service->electronicRouting($customer));
    }

    public function test_taxable_french_association_requires_a_siren(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer([
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'tax_subject_status' => Customer::TAX_STATUS_TAXABLE_NOT_VAT_LIABLE,
            'country' => 'FR',
            'legal_name' => 'Association Exemple',
            'address' => '1 rue du Test', 'zipcode' => '75001', 'city' => 'Paris',
        ]);

        $this->assertFalse($service->isComplete($customer));
        $customer->siren = '123456789';
        $this->assertTrue($service->isComplete($customer));
        $this->assertSame(FiscalProfileService::ROUTING_EINVOICING, $service->electronicRouting($customer));
    }

    public function test_unknown_association_requires_manual_review(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer([
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'tax_subject_status' => Customer::TAX_STATUS_UNKNOWN,
            'country' => 'FR',
            'legal_name' => 'Association Exemple',
            'address' => '1 rue du Test', 'zipcode' => '75001', 'city' => 'Paris',
        ]);

        $this->assertFalse($service->isComplete($customer));
        $this->assertTrue($service->isReadyForIssuance($customer));
        $this->assertSame(FiscalProfileService::ROUTING_MANUAL_REVIEW, $service->electronicRouting($customer));
    }

    public function test_incomplete_business_is_not_ready_for_issuance(): void
    {
        $customer = new Customer([
            'customer_type' => Customer::TYPE_BUSINESS,
            'country' => 'FR',
            'legal_name' => 'Example SAS',
        ]);

        $this->assertFalse((new FiscalProfileService)->isReadyForIssuance($customer));
    }

    public function test_vat_liable_association_requires_its_vat_number(): void
    {
        $service = new FiscalProfileService;
        $customer = new Customer([
            'customer_type' => Customer::TYPE_ASSOCIATION,
            'tax_subject_status' => Customer::TAX_STATUS_VAT_LIABLE,
            'country' => 'FR',
            'legal_name' => 'Association Exemple',
            'siren' => '123456789',
            'address' => '1 rue du Test', 'zipcode' => '75001', 'city' => 'Paris',
        ]);

        $this->assertFalse($service->isComplete($customer));
        $customer->vat_number = 'FR12123456789';
        $this->assertTrue($service->isComplete($customer));
    }
}
