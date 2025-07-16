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
namespace Tests\Feature\Client;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Upgrade;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Services\Store\TaxesService;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\ServerSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_index(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);

        $user = $this->createCustomerModel();
        for ($index = 0; $index < 15; $index++) {
            $this->createServiceModel($user->id);
        }
        $this->actingAs($user)->get(route('front.services.index'))->assertOk();
    }

    public function test_services_valid_filter(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);

        Customer::factory(15)->create();
        $user = $this->createCustomerModel();
        for ($index = 0; $index < 15; $index++) {
            $this->createServiceModel($user->id);
        }
        $this->actingAs($user)->get(route('front.services.index').'?filter=active')->assertOk();
    }

    public function test_services_invalid_filter(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.services.index').'?filter=suuuu')->assertRedirect();
    }

    public function test_services_can_show(): void
    {
        $this->seed(\Database\Seeders\ModuleSeeder::class);
        app('extension')->autoload(app());

        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var Service $service */
        $service = Service::factory()->create();
        /** @var Customer $user */
        $user = $service->customer;
        $this->actingAs($user)->get(route('front.services.show', ['service' => $service->uuid]))->assertOk();
    }

    public function test_services_renewals(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var Service $service */
        $service = Service::factory()->create();
        /** @var Customer $user */
        $user = $service->customer;

        $this->actingAs($user)->get(route('front.services.renewal', ['service' => $service->uuid]))->assertOk();
    }

    public function test_services_cannot_show(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        /** @var Service $service */
        $service = Service::factory()->create();
        /** @var Customer $user */
        $user = Customer::where('id', '!=', $service->customer_id)->first();

        $this->actingAs($user)->get(route('front.services.show', ['service' => $service->uuid]))->assertNotFound();
    }

    public function test_services_show_options(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        /** @var Customer $user */
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);

        $configOptions = $this->createOptionModel();
        $service->configoptions()->create([
            'config_option_id' => $configOptions->id,
            'key' => $configOptions->key,
            'value' => '10',
            'expires_at' => now()->addDays(10),
            'quantity' => 1,
        ]);
        $this->actingAs($customer)->get(route('front.services.options', ['service' => $service->uuid]))->assertOk();
    }

    public function test_services_show_upgrade(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $product2->sort_order = 10;
        $product2->save();
        $service = $this->createServiceModel($customer->id);
        $service->update([
            'product_id' => $product->id,
            'status' => 'active',
        ]);
        $this->actingAs($customer)->get(route('front.services.upgrade', ['service' => $service->uuid]))->assertOk();
    }

    public function test_services_cannot_show_upgrade(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $this->actingAs($customer)->get(route('front.services.upgrade', ['service' => $service->uuid]))->assertNotFound();
    }

    public function test_services_show_upgrade_process(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $product2->sort_order = 10;
        $product2->save();
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $service->update([
            'product_id' => $product->id,
            'status' => 'active',
        ]);

        $product = $this->createProductModel();
        $response = $this->actingAs($customer)->get(route('front.services.upgrade_process', ['service' => $service->uuid, 'product' => $product->id]));
        $response->assertRedirect();
        $this->assertDatabaseCount('upgrades', 1);
        $this->assertDatabaseHas('upgrades', [
            'service_id' => $service->id,
            'old_product_id' => $service->product_id,
            'new_product_id' => $product->id,
        ]);
    }

    public function test_service_show_upgrade_process_service_expiring_soon(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $product2->sort_order = 10;
        $product2->save();
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $service->update([
            'product_id' => $product->id,
            'status' => 'active',
            'expires_at' => now()->addDays(1),
        ]);
        $service->refresh();

        $product = $this->createProductModel();
        $response = $this->actingAs($customer)->get(route('front.services.upgrade_process', ['service' => $service->uuid, 'product' => $product->id]));
        $response->assertRedirect();
        $this->assertDatabaseCount('upgrades', 1);
        $upgrade = Upgrade::where('service_id', $service->id)->first();
        $invoice = $upgrade->invoice;
        $this->assertDatabaseCount('invoice_items', 2);
        $first = InvoiceItem::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('renewal', $first->type);
        $this->assertEquals($first->unit_price_ht, $service->getBillingPrice()->price);
        $second = InvoiceItem::where('invoice_id', $invoice->id)->where('type', 'upgrade')->first();
        dump($second->unit_price_ht);
    }

    public function test_services_change_name(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);

        $this->actingAs($customer)->post(route('front.services.name', ['service' => $service->uuid]), [
            'name' => 'test',
        ])->assertRedirect(route('front.services.show', ['service' => $service->uuid]));
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'test',
        ]);
    }

    public function test_services_change_name_invalid(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);

        $this->actingAs($customer)->post(route('front.services.name', ['service' => $service->uuid]), [
            'name' => '',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_services_change_simple_billing(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $this->actingAs($customer)->post(route('front.services.billing', ['service' => $service->uuid]), [
            'billing' => 'quarterly',
            'gateway' => 'balance',
        ])->assertRedirect();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'billing' => 'quarterly',
        ]);
        $service->refresh();
        $this->assertEquals($service->getBillingPrice()->price, 12);
    }

    public function test_services_change_simple_billing_invalid(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $this->actingAs($customer)->post(route('front.services.billing', ['service' => $service->uuid]), [
            'billing' => 'annually',
        ])->assertSessionHasErrors(['billing']);
    }

    public function test_services_change_billing_and_pay(): void
    {
        $this->seed(ServerSeeder::class);
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id, 'active', ['monthly' => 10, 'quarterly' => 12]);
        /** @var Customer $user */
        $user = $service->customer;

        $response = $this->actingAs($user)->post(route('front.services.billing', ['service' => $service->uuid]), [
            'billing' => 'quarterly',
            'gateway' => 'balance',
            'pay' => 'on',
        ]);
        $service->refresh();
        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'billing' => 'quarterly',
        ]);
        $service->refresh();
        $invoice = Invoice::find($service->invoice_id);
        $this->assertEquals($service->getBillingPrice()->price, $invoice->subtotal);
    }

    public function test_services_can_renew_because_is_expired(): void
    {
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(ServerSeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $service->status = 'expired';
        $service->save();
        $this->actingAs($customer)->get(route('front.services.renew', ['service' => $service->id, 'gateway' => 'balance']))->assertRedirect(route('front.services.show', ['service' => $service->uuid]));
    }

    public function test_services_cannot_renew_because_max_renewal_is_attempts(): void
    {
        $this->seed(ServerSeeder::class);

        $this->seed(\Database\Seeders\ModuleSeeder::class);
        app('extension')->autoload(app());

        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $customer = $this->createCustomerModel();
        /** @var Service $service */
        $service = $this->createServiceModel($customer->id);
        $service->status = 'active';
        $service->renewals = 11;
        $service->max_renewals = 10;
        $service->save();
        $this->actingAs($customer)->get(route('front.services.renew', ['service' => $service->id, 'gateway' => 'balance']))->assertRedirect(route('front.services.show', ['service' => $service->uuid]));
    }

    public function test_services_can_renew(): void
    {
        $this->seed(ServerSeeder::class);

        $this->seed(\Database\Seeders\ModuleSeeder::class);
        app('extension')->autoload(app());

        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(15)->create();
        /** @var ServiceRenewals $renewal */
        $renewal = ServiceRenewals::factory()->create();
        /** @var Service $service */
        $service = $renewal->service;
        /** @var Invoice $invoice */
        $invoice = $renewal->invoice;
        /** @var Customer $user */
        $user = $service->customer;
        $service->status = 'active';
        $service->renewals = 9;
        $service->max_renewals = 10;
        $service->save();
        $this->actingAs($user)->get(route('front.services.renew', ['service' => $service->id, 'gateway' => 'balance']))->assertRedirect();
        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertDatabaseCount('service_renewals', 2);
        $this->assertDatabaseCount('email_messages', 1);
        /** @var Service $service */
        $service = Service::find($service->id);
        $this->assertEquals($service->invoice_id, $invoice->id + 1);
        $invoice = Invoice::find($invoice->id + 1);
        $this->assertEquals($service->getBillingPrice()->price, $invoice->subtotal);
        $tax = TaxesService::getTaxAmount($service->getBillingPrice()->price, tax_percent());
        $total = number_format(TaxesService::getAmount($service->getBillingPrice()->price, tax_percent()) + $tax, 2);
        $this->assertEquals($total, $invoice->total);
        $this->assertEquals('pending', $invoice->status);
    }

    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InvoiceItem::truncate();
        Invoice::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
