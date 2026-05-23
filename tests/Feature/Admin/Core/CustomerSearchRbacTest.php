<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Provisioning\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSearchRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_service_field_blocks_admin_without_show_services(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create(['customer_id' => $customer->id]);

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.index', ['q' => $service->id, 'field' => 'service_id']),
            [],
            ['admin.show_customers']
        );
        $response->assertStatus(403);
    }

    public function test_search_invoice_field_blocks_admin_without_show_invoices(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'setupfees' => 0,
            'notes' => '',
            'currency' => 'EUR',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.index', ['q' => $invoice->id, 'field' => 'invoice_id']),
            [],
            ['admin.show_customers']
        );
        $response->assertStatus(403);
    }

    public function test_search_service_field_allows_admin_with_show_services(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create(['customer_id' => $customer->id]);

        $response = $this->performAdminAction(
            'GET',
            route('admin.customers.index', ['q' => $service->id, 'field' => 'service_id']),
            [],
            ['admin.show_customers', 'admin.show_services']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with SHOW_SERVICES must access service search');
    }
}
