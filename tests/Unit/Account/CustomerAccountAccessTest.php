<?php

namespace Tests\Unit\Account;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_keeps_full_service_and_invoice_permissions(): void
    {
        $owner = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);
        $invoice = Invoice::factory()->create(['customer_id' => $owner->id]);

        $this->assertTrue($owner->hasServicePermission($service, 'service.cancel'));
        $this->assertTrue($owner->hasInvoicePermission($invoice, 'invoice.pay'));
    }

    public function test_sub_user_service_permission_requires_permission_and_service_scope(): void
    {
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $allowedService = $this->createServiceModel($owner->id);
        $deniedService = $this->createServiceModel($owner->id);

        $access = CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'created_by_customer_id' => $owner->id,
            'permissions' => ['service.show'],
            'all_services' => false,
        ]);
        $access->services()->sync([$allowedService->id]);

        $this->assertTrue($subUser->hasServicePermission($allowedService, 'service.show'));
        $this->assertFalse($subUser->hasServicePermission($allowedService, 'service.renew'));
        $this->assertFalse($subUser->hasServicePermission($deniedService, 'service.show'));
        $this->assertTrue($subUser->receivedAccountAccesses()->exists());
    }

    public function test_accessible_scopes_include_delegated_resources(): void
    {
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);
        $invoice = Invoice::factory()->create(['customer_id' => $owner->id]);

        CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'created_by_customer_id' => $owner->id,
            'permissions' => ['service.show', 'invoice.show'],
            'all_services' => true,
        ]);

        $this->assertTrue(\App\Models\Provisioning\Service::accessibleBy($subUser)->whereKey($service->id)->exists());
        $this->assertTrue(Invoice::accessibleBy($subUser)->whereKey($invoice->id)->exists());
    }
}
