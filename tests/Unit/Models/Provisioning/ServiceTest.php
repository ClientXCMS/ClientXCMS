<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Tests\Unit\Models\Provisioning;

use App\Models\Account\Customer;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\StoreSeeder;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_renew_simple_service_if_active()
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(1)->create();
        $service = $this->createServiceModel(Customer::first()->id);
        $service->update(['billing' => 'monthly']);
        $invoice = InvoiceService::createInvoiceFromService($service);
        /** @var Customer $user */
        $user = $service->customer;
        $invoice->items[0]->type = 'renewal';
        $invoice->items[0]->related_id = $service->id;
        $invoice->items[0]->data = [
            'months' => 1,
        ];
        // 1 initial + 3 supplémentaires
        $now = $service->expires_at->addMonth();
        $invoice->items[0]->save();
        $service->status = 'active';
        $service->renewals = 1;
        $service->max_renewals = 10;
        $service->save();
        $invoice->items[0]->tryDeliver();
        $invoice->complete();
        $service = $service->fresh();
        $this->assertEquals($service->status, 'active');
        $this->assertEquals($service->max_renewals, 10);
        $this->assertEquals($service->renewals, 2);
        $this->assertEquals($now->format('d/m/y'), $service->expires_at->format('d/m/y'));
    }

    public function test_renew_with_custom_billing_if_active()
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(1)->create();
        $service = $this->createServiceModel(Customer::first()->id);
        $service->update(['billing' => 'quarterly']);
        $invoice = InvoiceService::createInvoiceFromService($service);
        /** @var Customer $user */
        $user = $service->customer;
        $invoice->items[0]->type = 'renewal';
        $invoice->items[0]->related_id = $service->id;
        $invoice->items[0]->data = [
            'months' => 3,
        ];
        // 3 initial + 3 supplémentaires
        $now = $service->expires_at->addMonths(3);
        $invoice->items[0]->save();
        $service->status = 'active';
        $service->renewals = 1;
        $service->max_renewals = 10;
        $service->save();
        $invoice->complete();
        $service = $service->fresh();
        $this->assertEquals($service->status, 'active');
        $this->assertEquals($service->max_renewals, 10);
        $this->assertEquals($service->renewals, 2);
        $this->assertEquals($now->format('d/m/y'), $service->expires_at->format('d/m/y'));
    }

    public function test_renew_service_if_expired()
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(1)->create();
        $service = $this->createServiceModel(Customer::first()->id);
        $service->update(['billing' => 'quarterly', 'expires_at' => Carbon::now()->subMonth(), 'status' => 'expired']);
        $invoice = InvoiceService::createInvoiceFromService($service);
        /** @var Customer $user */
        $user = $service->customer;
        $invoice->items[0]->type = 'renewal';
        $invoice->items[0]->related_id = $service->id;
        $invoice->items[0]->data = [
            'months' => 3,
        ];
        // 3 initial + 3 supplémentaires
        $now = $service->expires_at->addMonths(3);
        $invoice->items[0]->save();
        $service->renewals = 1;
        $service->max_renewals = 10;
        $service->save();
        $invoice->complete();
        $service = $service->fresh();
        $this->assertEquals($service->status, 'active');
        $this->assertEquals($service->max_renewals, 10);
        $this->assertEquals($service->renewals, 2);
        $this->assertEquals($now->format('d/m/y'), $service->expires_at->format('d/m/y'));
    }

    public function test_get_billing_price_with_synchronized_product()
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(1)->create();
        $service = $this->createServiceModel(Customer::first()->id, 'active', []);
        $product = $this->createProductModel();
        $service->product_id = $product->id;
        $service->save();
        $service->refresh();
        $this->assertEquals($service->getPricing()->monthly, $product->getPriceByCurrency('USD', 'monthly')->price);
        $this->assertEquals($service->getPricing()->setup_monthly, $product->getPriceByCurrency('USD', 'monthly')->setup);
    }

    public function test_get_billing_price_with_synchronized_product_with_recurring()
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(1)->create();
        $service = $this->createServiceModel(Customer::first()->id, 'active', []);
        $product = $this->createProductModel('active', 1, ['quarterly' => 1, 'setup_quarterly' => 1, 'monthly' => 1, 'setup_monthly' => 1, 'semiannually' => 1, 'setup_semiannually' => 1]);

        $service->product_id = $product->id;
        $service->save();
        $service->refresh();
        $this->assertEquals($service->getPricing()->semiannually, $product->getPriceByCurrency('USD', 'semiannually')->price);
    }
}
