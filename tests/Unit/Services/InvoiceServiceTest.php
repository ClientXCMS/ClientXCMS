<?php

namespace Tests\Unit\Services;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use App\Models\Store\Coupon;
use App\Services\Billing\InvoiceService;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_service_on_invoice_completion()
    {
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(20)->create();
        $this->createProductModel();

        app('extension')->autoload(app());
        \Artisan::call('migrate');

        $this->seed(StoreSeeder::class);
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::factory()->create();
        /** @var Invoice $invoice */
        $invoice = $invoiceItem->invoice;
        $invoice->complete();
        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();

        $this->assertEquals($email->recipient, $invoice->customer->email);
        $this->assertEquals($email->recipient_id, $invoice->customer_id);
        $this->assertDatabaseCount('service_renewals', 1);
        $this->assertEquals(true, ServiceRenewals::first()->first_period);
    }

    public function test_create_invoice_with_coupon()
    {
        $coupon = $this->createCouponModel();
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        /** @var Basket $basket */
        $basket = $this->createBasketForCustomer($user);
        $basket->coupon_id = $coupon->id;
        $basket->save();
        /** @var BasketRow $basketRow */
        $basketRow = BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertNotEmpty($item->discount);
        $this->assertEquals($item->discountTotal(), 1);
        $this->assertEquals($invoice->total, 10.80);
    }

    public function test_create_invoice_with_coupon_with_setup()
    {
        $coupon = $this->createCouponModel('percent', ['monthly' => 10, 'setup_monthly' => 10]);
        $user = $this->createCustomerModel();
        $product = $this->createProductModel('active', 1, ['monthly' => 10, 'setup_monthly' => 10]);
        $this->seed(EmailTemplateSeeder::class);
        /** @var Basket $basket */
        $basket = $this->createBasketForCustomer($user);
        $basket->coupon_id = $coupon->id;
        $basket->save();
        $basketRow = BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertNotEmpty($item->discount);
        $this->assertEquals($item->discountTotal(), 2);
        $this->assertEquals($invoice->tax, 3.6);
        $this->assertEquals($invoice->subtotal, 18);
    }

    public function test_simple_create_invoice_from_basket()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);

        $basket = Basket::create([
            'user_id' => $user->id,
            'ipaddress' => request()->ip(),
            'completed_at' => '2021-01-01 00:00:01',
            'uuid' => 'aaaa-aaaa-aaaa-aaaa',
        ]);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->total, 12);
        $this->assertEquals($invoice->subtotal, 10);
        $this->assertEquals($invoice->tax, 2);
        $this->assertEquals($invoice->setupfees, 0);
        $this->assertEquals($invoice->currency, 'USD');
        $this->assertEquals($invoice->status, 'pending');
        $this->assertEquals($invoice->notes, "Created from basket #{$basket->id}");
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();
        $this->assertEquals($email->recipient, $user->email);
        $this->assertEquals($email->recipient_id, $user->id);
    }

    public function test_simple_create_invoice_with_setup_from_basket()
    {

        $user = $this->createCustomerModel();
        $product = $this->createProductModel('active', 1, ['monthly' => 10, 'setup_monthly' => 10]);
        $this->seed(EmailTemplateSeeder::class);

        $basket = Basket::create([
            'user_id' => $user->id,
            'ipaddress' => request()->ip(),
            'completed_at' => '2021-01-01 00:00:01',
            'uuid' => 'aaaa-aaaa-aaaa-aaaa',
        ]);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->total, 24);
        $this->assertEquals($invoice->subtotal, 20);
        $this->assertEquals($invoice->tax, 4);
        $this->assertEquals($invoice->setupfees, 10);
        $this->assertEquals($invoice->currency, 'USD');
        $this->assertEquals($invoice->status, 'pending');
        $this->assertEquals($invoice->notes, "Created from basket #{$basket->id}");
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();
        $this->assertEquals($email->recipient, $user->email);
        $this->assertEquals($email->recipient_id, $user->id);
    }

    public function test_create_invoice_from_basket_with_option()
    {
        $user = $this->createCustomerModel();
        [$product, $option] = $this->createProductModelWithOption();
        $product->refresh();
        $this->seed(EmailTemplateSeeder::class);

        $basket = Basket::create([
            'user_id' => $user->id,
            'ipaddress' => request()->ip(),
            'completed_at' => '2021-01-01 00:00:01',
            'uuid' => 'aaaa-aaaa-aaaa-aaaa',
        ]);
        /** @var BasketRow $basketRow */
        $basketRow = BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $basketRow->addOption('key', 'value');
        $basketRow->refresh();
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals($invoice->total, 36);
        $this->assertEquals($invoice->subtotal, 30);
        $this->assertEquals($invoice->tax, 6);
        $this->assertEquals($invoice->setupfees, 0);
        $this->assertEquals($invoice->currency, 'USD');
        $this->assertEquals($invoice->status, 'pending');
        $this->assertEquals($invoice->notes, "Created from basket #{$basket->id}");
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();
        $this->assertEquals($email->recipient, $user->email);
        $this->assertEquals($email->recipient_id, $user->id);
    }

    public function test_update_existing_invoice_from_basket()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        $basketRow = BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertEquals($invoice->items->first()->related_id, $product->id);
        $this->assertEquals(20, $invoice->subtotal); // 10 * 2
        $product2 = $this->createProductModel('active', 1, ['monthly' => 20]);
        $basketRow->product_id = $product2->id;
        $basketRow->quantity = 1;
        $basketRow->save();
        $basketRow->refresh();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals($invoice->subtotal, 20); // 20
    }

    public function test_create_invoice_with_multiple_items()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals($invoice->total, 36);
        $this->assertEquals($invoice->subtotal, 30);
        $this->assertEquals($invoice->tax, 6);
        $this->assertEquals($invoice->setupfees, 0);
        $this->assertEquals($invoice->currency, 'USD');
        $this->assertEquals($invoice->status, 'pending');
        $this->assertEquals($invoice->notes, "Created from basket #{$basket->id}");
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();
        $this->assertEquals($email->recipient, $user->email);
        $this->assertEquals($email->recipient_id, $user->id);
    }

    public function test_create_invoice_with_multiple_items_and_multiple_quantities()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $product2 = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals($invoice->total, 60);
        $this->assertEquals($invoice->subtotal, 50);
        $this->assertEquals($invoice->tax, 10);
        $this->assertEquals($invoice->setupfees, 0);
        $this->assertEquals($invoice->currency, 'USD');
        $this->assertEquals($invoice->status, 'pending');
        $this->assertEquals($invoice->notes, "Created from basket #{$basket->id}");
        $this->assertDatabaseCount('email_messages', 1);
        $email = EmailMessage::first();
        $this->assertEquals($email->recipient, $user->email);
        $this->assertEquals($email->recipient_id, $user->id);
    }

    public function test_create_invoice_with_simple_coupon()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $coupon = $this->createCouponModel('fixed', ['monthly' => 2]);
        $basket->coupon_id = $coupon->id;
        $basket->save();
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertNotEmpty($item->discount);
        $this->assertEquals($item->discountTotal(), 2);
        $this->assertEquals($invoice->total, 21.6);
    }

    public function test_create_invoice_with_complex_coupon()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel('active', 10, ['monthly' => 10, 'setup_monthly' => 10]);
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $coupon = $this->createCouponModel('percent', ['monthly' => 10, 'setup_monthly' => 10]);
        $basket->coupon_id = $coupon->id;
        $basket->save();
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertNotEmpty($item->discount);
        $this->assertEquals($item->discountTotal(), 4);
        $this->assertEquals($invoice->total, 43.2);
    }

    public function test_append_service_on_existing_invoice()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $service = $this->createServiceModel(auth()->user()->id, 'active');
        InvoiceService::appendServiceOnExistingInvoice($service, $invoice);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertEquals($invoice->total, 30 * 1.20);
    }

    public function test_create_invoice_from_service()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
    }

    public function test_create_invoice_from_service_with_options()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $option = $this->createOptionModel('text', 'test', ['monthly' => 10]);
        $configOptionService = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test',
            'value' => 'test',
            'expires_at' => $service->expires_at,
        ]);
        $this->createPriceModel($configOptionService->id, 'USD', ['monthly' => 10], 'config_options_service');
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        $item = $invoice->items->last();
        $this->assertEquals($item->related_id, $configOptionService->id);
        $this->assertEquals($item->type, 'config_option_service');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        $this->assertEquals($invoice->total, 20 * 1.20);
    }

    public function test_create_invoice_from_service_with_options_with_quantities()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $option = $this->createOptionModel('slider', 'test', ['monthly' => 1]);
        $configOption = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test',
            'value' => 10,
            'expires_at' => $service->expires_at,
        ]);
        $this->createPriceModel($configOption->id, 'USD', ['monthly' => 1], 'config_options_service');

        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        $item = $invoice->items->last();
        $this->assertEquals($item->related_id, $configOption->id);
        $this->assertEquals($item->type, 'config_option_service');
        $this->assertEquals($item->quantity, 10);
        $this->assertEquals($item->unit_price_ht, 1);
        $this->assertEquals($item->unit_price_ttc, 1.2);
        $this->assertEquals($invoice->total, 12 + (10 * 1.2));
    }

    public function test_create_invoice_from_service_with_multiples_options()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $option = $this->createOptionModel('text', 'test', ['monthly' => 10]);
        $configOptionService = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test',
            'value' => 'test',
            'expires_at' => $service->expires_at,
        ]);
        $this->createPriceModel($configOptionService->id, 'USD', ['monthly' => 10], 'config_options_service');
        $configOptionService = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test2',
            'value' => 'test2',
            'expires_at' => $service->expires_at,
        ]);
        $this->createPriceModel($configOptionService->id, 'USD', ['monthly' => 10], 'config_options_service');
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 3);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        /** @var InvoiceItem $item */
        $item = $invoice->items->last();
        $this->assertEquals($item->type, 'config_option_service');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
    }

    public function test_create_invoice_from_service_with_onetime_options()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $option = $this->createOptionModel('text', 'test', ['onetime' => 10]);
        ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test',
            'value' => 'test',
            'expires_at' => null,
        ]);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
    }

    public function test_create_invoice_from_service_with_onetime_and_many_recurring_options()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $service = $this->createServiceModel($user->id, 'active');
        $option = $this->createOptionModel('slider', 'test', ['onetime' => 10]);
        $configOptionService = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test',
            'value' => 10,
            'expires_at' => null,
        ]);
        $this->createPriceModel($configOptionService->id, 'USD', ['onetime' => 10], 'config_options_service');
        $configOptionService = ConfigOptionService::create([
            'service_id' => $service->id,
            'config_option_id' => $option->id,
            'key' => 'test2',
            'value' => 10,
            'expires_at' => $service->expires_at,
        ]);
        $this->createPriceModel($configOptionService->id, 'USD', ['monthly' => 10], 'config_options_service');
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        /** @var InvoiceItem $item */
        $item = InvoiceItem::where('type', '=', 'renewal')->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        /** @var InvoiceItem $item */
        $item = InvoiceItem::where('type', '=', 'config_option_service')->first();
        $this->assertEquals($item->type, 'config_option_service');
        $this->assertEquals($item->quantity, 10);
        $this->assertEquals($item->unit_price_ht, 10);
        $this->assertEquals($item->unit_price_ttc, 12);
        $this->assertEquals($invoice->total, 12 + (100 * 1.2));
    }

    public function test_create_service_from_invoice_item()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        BasketRow::insert([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);

        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $invoice->complete();
        $this->assertDatabaseCount('services', 2);
        $service = Service::first();
        $this->assertEquals($service->customer_id, $user->id);
        $this->assertEquals($service->product_id, $product->id);
        $this->assertEquals($service->billing, 'monthly');
        $this->assertEquals($service->currency, 'USD');
        $this->assertEquals($service->status, 'pending');
        $this->assertEquals($service->expires_at->format('d/m/y'), $service->created_at->addMonth()->format('d/m/y'));
    }

    public function test_create_service_from_invoice_item_with_simple_options()
    {
        $user = $this->createCustomerModel();
        [$product, $option] = $this->createProductModelWithOption();
        $product->refresh();
        $option->refresh();
        $this->seed(EmailTemplateSeeder::class);
        $basket = $this->createBasketForCustomer($user);
        $row = BasketRow::create([
            'basket_id' => $basket->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'billing' => 'monthly',
            'currency' => 'USD',
            'options' => '{}',
            'data' => '{}',
        ]);
        $row->addOption('key', 'value');
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        $invoice->complete();
        $this->assertDatabaseCount('services', 1);
        /** @var ServiceRenewals $service */
        $service = Service::first();
        $this->assertEquals($service->customer_id, $user->id);
        $this->assertEquals($service->product_id, $product->id);
        $this->assertEquals($service->billing, 'monthly');
        $this->assertEquals($service->currency, 'USD');
        $this->assertEquals($service->status, 'pending');
        $this->assertDatabaseCount('config_options_services', 1);
        $configOptionService = ConfigOptionService::first();
        $this->assertEquals($configOptionService->service_id, $service->id);
        $this->assertEquals($configOptionService->config_option_id, $option->id);
        $this->assertDatabaseHas('pricings', [
            'related_id' => $configOptionService->id,
            'related_type' => 'config_options_service',
            'currency' => 'USD',
            'monthly' => 10,
        ]);
    }

    public function test_create_invoice_from_discounted_service()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $coupon = $this->createCouponModel('percent', ['monthly' => 10]);
        $service = $this->createServiceModel($user->id, 'active'); // 10% discount
        $service->attachMetadata('coupon_id', $coupon->id);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 9); // 10 - 10%
        $this->assertEquals($item->unit_price_ttc, 10.8); // 9 + 20%
    }

    public function test_create_invoice_from_service_with_no_applied_month()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $coupon = $this->createCouponModel('percent', ['monthly' => 10]);
        $coupon->applied_month = 0; // No discount applied
        $coupon->save();
        $service = $this->createServiceModel($user->id, 'active');
        $service->attachMetadata('coupon_id', $coupon->id);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10); // No discount
        $this->assertEquals($item->unit_price_ttc, 12); // No discount
    }

    public function test_create_invoice_from_service_with_applied_month()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $coupon = $this->createCouponModel('percent', ['monthly' => 10]);
        $coupon->applied_month = 3; // Discount applied for 3 months
        $coupon->save();
        $service = $this->createServiceModel($user->id, 'active');
        $service->attachMetadata('coupon_id', $coupon->id);
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 9); // 10 - 10%
        $this->assertEquals($item->unit_price_ttc, 10.8); // 9 + 20%
    }

    public function test_create_invoice_from_service_with_applied_month_exceeding_renewal_period()
    {
        $user = $this->createCustomerModel();
        $this->seed(EmailTemplateSeeder::class);
        $coupon = $this->createCouponModel('percent', ['monthly' => 10]);
        $coupon->applied_month = 2; // Discount applied for 6 months
        $coupon->save();
        $service = $this->createServiceModel($user->id, 'active');
        $service->attachMetadata('coupon_id', $coupon->id);
        $service->renewals = 2;
        $service->save();
        $this->be($user);
        $gateway = $this->createGatewayModel();
        $invoice = InvoiceService::createInvoiceFromService($service, $gateway);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        /** @var InvoiceItem $item */
        $item = $invoice->items->first();
        $this->assertEquals($item->related_id, $service->id);
        $this->assertEquals($item->type, 'renewal');
        $this->assertEquals($item->quantity, 1);
        $this->assertEquals($item->unit_price_ht, 10); // No discount
        $this->assertEquals($item->unit_price_ttc, 12); // No discount
    }

    public function test_create_invoice_from_product()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);

        $invoice = InvoiceService::createInvoiceFromProduct($user, $product, 'monthly', 'USD');
        $invoice->complete();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $user->id,
            'status' => 'paid',
            'currency' => 'USD',
        ]);
    }

    public function test_create_fresh_invoice_from_product()
    {
        $user = $this->createCustomerModel();
        $product = $this->createProductModel();
        $this->seed(EmailTemplateSeeder::class);

        $invoice = InvoiceService::createFreshInvoice($user->id, 'USD', 'Fresh invoice for product', []);
        $invoice->complete();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $user->id,
            'status' => 'paid',
            'currency' => 'USD',
        ]);
    }

    public function beforeRefreshingDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InvoiceItem::truncate();
        EmailMessage::truncate();
        ServiceRenewals::truncate();
        Service::truncate();
        Gateway::truncate();
        if (Schema::hasTable('config_options_services')) {
            ConfigOptionService::truncate();
        }
        BasketRow::truncate();
        Basket::truncate();
        Invoice::truncate();
        Coupon::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
