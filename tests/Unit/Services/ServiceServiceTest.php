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
