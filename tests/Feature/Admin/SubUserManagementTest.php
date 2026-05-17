<?php

namespace Tests\Feature\Admin;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_manage_customers_can_revoke_access(): void
    {
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $access = CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'permissions' => ['invoice.show'],
            'all_services' => true,
        ]);

        $this->performAdminAction('DELETE', route('admin.customers.subusers.destroy', [
            'customer' => $owner,
            'access' => $access,
        ]), [], ['admin.manage_customers'])->assertRedirect();

        $this->assertDatabaseMissing('customer_account_accesses', ['id' => $access->id]);
    }

    public function test_admin_without_manage_customers_cannot_revoke_access(): void
    {
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $access = CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'permissions' => ['invoice.show'],
            'all_services' => true,
        ]);

        $this->performAdminAction('DELETE', route('admin.customers.subusers.destroy', [
            'customer' => $owner,
            'access' => $access,
        ]), [], ['admin.show_customers'])->assertForbidden();

        $this->assertDatabaseHas('customer_account_accesses', ['id' => $access->id]);
    }
}
