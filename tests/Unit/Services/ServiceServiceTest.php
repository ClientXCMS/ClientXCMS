<?php

namespace Tests\Unit\Services;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Services\Billing\InvoiceService;
use App\Services\Provisioning\ServiceService;
use Database\Seeders\CancellationReasonSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ServiceServiceTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);
        Customer::factory(1)->create();
    }

    public function test_change_service_status_with_expire()
    {
        $mock = $this->mock(Service::class);
        $mock->shouldReceive('expire')->once()->with(true);
        $result = ServiceService::changeServiceStatus(request(), $mock, 'expire');
        $this->assertEquals($result[1], 'terminate');
    }

    public function test_change_service_status_with_suspend_without_reason()
    {
        $mock = $this->mock(Service::class);
        $mock->shouldReceive('suspend')->once()->withAnyArgs();
        $result = ServiceService::changeServiceStatus(request(), $mock, 'suspend');
        $this->assertEquals($result[1], 'suspend');
    }

    public function test_change_service_status_with_reason()
    {
        $mock = $this->mock(Service::class);
        $mock->shouldReceive('suspend')->once()->withAnyArgs();
        $request = request()->merge(['reason' => 'test']);
        $result = ServiceService::changeServiceStatus($request, $mock, 'suspend');
        $this->assertEquals($result[1], 'suspend');
    }

    public function test_change_service_status_with_notify()
    {
        $service = Service::factory()->create();
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('suspend')->once()->withAnyArgs();
        $request = request()->merge(['notify' => true]);
        $result = ServiceService::changeServiceStatus($request, $mock, 'suspend');
        $this->assertEquals($result[1], 'suspend');
        $result = ServiceService::changeServiceStatus($request, $service, 'suspend');
        $this->assertDatabaseCount('email_messages', 1); // DOUBLE CALL
    }

    public function test_change_service_status_with_unsuspend()
    {
        $mock = $this->mock(Service::class);
        $mock->shouldReceive('unsuspend')->once();
        $result = ServiceService::changeServiceStatus(request(), $mock, 'unsuspend');
        $this->assertEquals($result[1], 'unsuspend');
    }

    public function test_change_service_status_with_cancel()
    {
        $service = Service::factory()->create();
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('cancel')->once()->withAnyArgs();
        request()->merge(['expiration' => 'now']);
        $result = ServiceService::changeServiceStatus(request(), $mock, 'cancel');
        $this->assertEquals($result[1], 'cancel');
    }

    public function test_change_service_status_with_cancel_already_cancelled()
    {
        $service = Service::factory()->create();
        $service->cancelled_at = now();
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('uncancel')->once();
        $result = ServiceService::changeServiceStatus(request(), $mock, 'cancel');
        $this->assertEquals($result[1], 'uncancel');
    }

    public function test_change_service_status_with_cancel_with_reason()
    {
        $this->seed(CancellationReasonSeeder::class);
        $service = Service::factory()->create();
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('cancel')->once()->withAnyArgs();
        request()->merge(['reason' => '1']);
        $result = ServiceService::changeServiceStatus(request(), $mock, 'cancel');
        $this->assertEquals($result[1], 'cancel');
    }

    public function test_change_status_with_cancel_with_bad_reason()
    {
        $this->seed(CancellationReasonSeeder::class);
        $service = Service::factory()->create();
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('cancel')->once()->withAnyArgs();
        request()->merge(['reason' => 'bad']);
        $result = ServiceService::changeServiceStatus(request(), $mock, 'cancel');
        $this->assertEquals($result[1], 'cancel');
    }

    public function test_change_service_status_with_cancel_with_end_of_period()
    {
        $service = Service::factory()->create();
        $service->expires_at = now()->addDays(3);
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('cancel')->once()->withAnyArgs();
        $request = request()->merge(['expiration' => 'end_of_period']);
        $result = ServiceService::changeServiceStatus($request, $mock, 'cancel');
        $this->assertEquals($result[1], 'cancel');
        $result = ServiceService::changeServiceStatus($request, $service, 'cancel');
        $this->assertEquals($mock->cancelled_at->format('d/m/Y'), $service->expires_at->format('d/m/Y'));
    }

    public function test_change_service_status_with_cancel_with_now()
    {
        $service = Service::factory()->create();
        $service->expires_at = now()->addDays(15);
        $mock = \Mockery::mock($service)->makePartial();
        $mock->shouldReceive('cancel')->once()->withAnyArgs();
        $request = request()->merge(['expiration' => 'now']);
        $result = ServiceService::changeServiceStatus($request, $mock, 'cancel');
        $this->assertEquals($result[1], 'cancel');
        $result = ServiceService::changeServiceStatus($request, $service, 'cancel');
        $this->assertEquals($service->cancelled_at->format('d/m/Y'), now()->format('d/m/Y'));
    }

    public function test_create_renewal_invoice_with_onetime_billing()
    {
        /** @var Service $service */
        $service = Service::factory()->create();
        $service->billing = 'onetime';
        $this->expectException(\Exception::class);
        ServiceService::createRenewalInvoice($service, 'onetime');
    }

    public function test_create_renewal_invoice_with_invalid_mode()
    {
        /** @var Service $service */
        $service = Service::factory()->create();
        $this->expectException(\Exception::class);
        ServiceService::createRenewalInvoice($service, 'onetime', 'invalid');
    }

    public function test_create_renewal_invoice_with_two_billing()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10, 'quarterly' => 12]);
        $invoice = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->id, $service->invoice_id);
        $this->assertEquals(10, $invoice->subtotal);
        $invoice = ServiceService::createRenewalInvoice($service, 'quarterly', InvoiceService::CREATE_INVOICE);
        $this->assertEquals(12, $invoice->subtotal);
    }

    public function test_create_renewal_invoice_with_single_billing()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10]);
        $invoice = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->id, $service->invoice_id);
        $this->assertEquals($invoice->subtotal, 10);
    }

    public function test_create_renewal_invoice_in_append()
    {
        $invoice = Invoice::factory()->create(['customer_id' => Customer::first()->id, 'total' => 0]);
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10]);
        $service->update(['invoice_id' => $invoice->id]);
        ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::APPEND_SERVICE, $invoice->id);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->id, $service->invoice_id);
        $invoice->refresh();
        $this->assertEquals($invoice->subtotal, 10);
    }

    /**
     * v2.16 — Anti-duplication regression test.
     * Calling createRenewalInvoice() twice in a row with the same billing
     * cycle must reuse the existing pending invoice, never create a second
     * one. Customers were previously able to spam the renew button.
     */
    public function test_create_renewal_invoice_is_idempotent_for_same_billing()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10]);

        $first = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $service->refresh();
        $second = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);

        $this->assertEquals($first->id, $second->id, 'Same billing cycle must reuse the pending invoice');
        $this->assertEquals(1, Invoice::where('customer_id', Customer::first()->id)->count(), 'Only one invoice must exist');
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseCount('service_renewals', 1);
    }

    /**
     * v2.16 — When the customer changes their billing cycle before paying,
     * the old pending invoice must be cancelled and a brand new one issued.
     * The partial unique index would otherwise block the new INSERT.
     */
    public function test_create_renewal_invoice_cancels_old_when_billing_changes()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10, 'quarterly' => 12]);

        $first = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $service->refresh();
        $second = ServiceService::createRenewalInvoice($service, 'quarterly', InvoiceService::CREATE_INVOICE);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertEquals(Invoice::STATUS_CANCELLED, Invoice::find($first->id)->status);
        $this->assertEquals(Invoice::STATUS_PENDING, $second->status);
        $this->assertEquals(12, $second->subtotal);

        // Exactly one pending renewal row remains for this service.
        $this->assertEquals(
            1,
            ServiceRenewals::where('service_id', $service->id)
                ->where('status', ServiceRenewals::STATUS_PENDING)
                ->whereNull('deleted_at')
                ->count()
        );
    }

    /**
     * v2.16 — Once a pending invoice is cancelled (by the customer or by
     * staff), the InvoiceObserver releases the pending lock so a brand new
     * renewal attempt can succeed.
     */
    public function test_pending_renewal_lock_is_released_when_invoice_is_cancelled()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10]);

        $first = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $first->cancel();
        $service->refresh();

        $second = ServiceService::createRenewalInvoice($service, 'monthly', InvoiceService::CREATE_INVOICE);
        $this->assertNotEquals($first->id, $second->id, 'A fresh invoice must be issued after cancellation');
        $this->assertEquals(Invoice::STATUS_PENDING, $second->status);
    }

    /**
     * v2.16 — Defence-in-depth: even if some legacy code path bypasses the
     * service layer, the database partial unique index must reject a second
     * pending row for the same service.
     */
    public function test_database_unique_index_blocks_concurrent_pending_renewals()
    {
        $service = $this->createServiceModel(Customer::first()->id, 'active', ['monthly' => 10]);
        $invoice = Invoice::factory()->create(['customer_id' => Customer::first()->id, 'total' => 0]);

        ServiceRenewals::create([
            'service_id' => $service->id,
            'invoice_id' => $invoice->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'period' => 1,
            'status' => ServiceRenewals::STATUS_PENDING,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ServiceRenewals::create([
            'service_id' => $service->id,
            'invoice_id' => $invoice->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'period' => 2,
            'status' => ServiceRenewals::STATUS_PENDING,
        ]);
    }

    public function beforeRefreshingDatabase()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InvoiceItem::truncate();
        EmailMessage::truncate();
        Invoice::truncate();
        ServiceRenewals::truncate();
        Service::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
