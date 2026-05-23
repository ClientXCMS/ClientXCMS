<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function subscription(): Subscription
    {
        $customer = Customer::factory()->create();

        return Subscription::create([
            'customer_id' => $customer->id,
            'service_id' => null,
            'payment_method_id' => 'pm_test',
            'state' => 'active',
            'cycles' => 1,
            'billing_day' => 5,
        ]);
    }

    public function test_destroy_blocks_admin_without_permission(): void
    {
        $sub = $this->subscription();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.subscriptions.destroy', $sub),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
        $this->assertDatabaseHas('subscriptions', ['id' => $sub->id, 'deleted_at' => null]);
    }

    public function test_destroy_allows_admin_with_manage_invoices(): void
    {
        $sub = $this->subscription();
        $response = $this->performAdminAction(
            'DELETE',
            route('admin.subscriptions.destroy', $sub),
            [],
            ['admin.manage_invoices']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_INVOICES must not be blocked');
    }
}
