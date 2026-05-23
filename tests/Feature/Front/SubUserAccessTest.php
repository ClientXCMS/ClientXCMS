<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Billing\Invoice;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_user_can_see_allowed_service_in_index(): void
    {
        $this->seed(StoreSeeder::class);
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $allowed = $this->createServiceModel($owner->id);
        $denied = $this->createServiceModel($owner->id);

        $access = CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'permissions' => ['service.show'],
            'all_services' => false,
        ]);
        $access->services()->sync([$allowed->id]);

        $this->actingAs($subUser)
            ->get(route('front.services.index'))
            ->assertOk()
            ->assertSee($allowed->excerptName())
            ->assertDontSee($denied->excerptName());
    }

    public function test_sub_user_without_service_permission_gets_404_on_direct_route(): void
    {
        $this->seed(StoreSeeder::class);
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'permissions' => ['invoice.show'],
            'all_services' => true,
        ]);

        $this->actingAs($subUser)
            ->get(route('front.services.show', $service))
            ->assertNotFound();
    }

    public function test_invoice_permissions_are_applied_separately(): void
    {
        $this->seed(StoreSeeder::class);
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $owner->id, 'status' => Invoice::STATUS_PENDING]);

        CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'permissions' => ['invoice.show'],
            'all_services' => false,
        ]);

        $this->actingAs($subUser)
            ->get(route('front.invoices.index'))
            ->assertOk()
            ->assertSee($invoice->identifier());

        $this->actingAs($subUser)
            ->get(route('front.invoices.download', $invoice))
            ->assertNotFound();
    }
}
