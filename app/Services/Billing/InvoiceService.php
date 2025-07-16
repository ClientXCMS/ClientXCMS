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
namespace App\Services\Billing;

use App\Addons\Freetrial\DTO\FreetrialDTO;
use App\Addons\Freetrial\Models\FreetrialConfig;
use App\DTO\Admin\Invoice\AddProductToInvoiceDTO;
use App\DTO\Store\ConfigOptionDTO;
use App\DTO\Store\ProductPriceDTO;
use App\DTO\Store\UpgradeDTO;
use App\Events\Core\Invoice\InvoiceCreated;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use App\Services\Store\TaxesService;
use Carbon\Carbon;

class InvoiceService
{
    const PRO_FORMA = 'proforma';

    const INVOICE = 'invoice';

    const APPEND_SERVICE = 'append_service';

    const CREATE_INVOICE = 'create_invoice';

    public static function createInvoiceFromBasket(Basket $basket, Gateway $gateway): Invoice
    {
        // On sauvegarde tout les champs de la table invoice sans les codes promotionnelle.
        $currency = $basket->items->first()->currency;
        // Si une facture est déjà liée au panier, on la met à jour
        if ($basket->getMetadata('invoice') != null) {
            $invoice = Invoice::find($basket->getMetadata('invoice'));
            if ($invoice != null) {
                $invoice->update([
                    'customer_id' => $basket->user_id,
                    'due_date' => now()->addDays(7),
                    'total' => $basket->total(),
                    'subtotal' => $basket->subtotal(),
                    'tax' => $basket->tax(),
                    'setupfees' => $basket->setupWithoutCoupon(),
                    'currency' => $currency,
                    'status' => 'pending',
                    'notes' => "Created from basket #{$basket->id}",
                    'paymethod' => $gateway->uuid,
                ]);
                $invoice->items()->delete();
                self::createInvoiceItemsFromBasket($basket, $invoice);

                return $invoice;
            }
        }
        $days = setting('remove_pending_invoice', 0) != 0 ? setting('remove_pending_invoice') : 7;
        $invoice = Invoice::create([
            'customer_id' => $basket->user_id,
            'due_date' => now()->addDays($days),
            'total' => $basket->total(),
            'subtotal' => $basket->subtotal(),
            'tax' => $basket->tax(),
            'setupfees' => $basket->setupWithoutCoupon(),
            'currency' => $currency,
            'status' => 'pending',
            'notes' => "Created from basket #{$basket->id}",
            'paymethod' => $gateway->uuid,
            'invoice_number' => Invoice::generateInvoiceNumber(),
        ]);
        self::createInvoiceItemsFromBasket($basket, $invoice);
        $basket->attachMetadata('invoice', $invoice->id);
        $invoice->attachMetadata('basket', $basket->id);
        event(new InvoiceCreated($invoice));

        return $invoice;
    }

    public static function createServicesFromInvoiceItem(Invoice $invoice, InvoiceItem $item): array
    {
        if (! in_array($item->type, ['service', 'free_trial'])) {
            return [];
        }
        $product = $item->relatedType();

        if ($item->type == 'free_trial' && class_exists(FreetrialConfig::class)) {
            /** @var FreetrialDTO $free_trial */
            $free_trial = $item->relatedType();
            /** @var Product $product */
            $product = $free_trial->getConfig()->product;
            $trial_ends_at = $free_trial->getConfig()->endDate();
            if ($product == null){
                throw new \Exception('Product not found for free trial');
            }
            $price = $product->getPriceByCurrency($invoice->currency, $item->billing());
            $item->unit_price_ht = $price->recurringPayment() + $price->onetimePayment();
            $item->unit_price_ttc = TaxesService::getVatPrice($item->unit_price_ht);
            $item->unit_setup_ht = $price->setup();
            $item->unit_setup_ttc = TaxesService::getVatPrice($item->unit_setup_ht);

        } else {
            $trial_ends_at = null;
        }
        $expiresAt = app(RecurringService::class)->addFromNow($item->billing());
        if ($item->billing() == 'onetime') {
            $next = null;
        } else {
            if ($trial_ends_at != null) {
                $next = $trial_ends_at;
                $expiresAt = $trial_ends_at;
            } else {
                $next = app(RecurringService::class)->addFromNow($item->billing())->subDays(setting('core.services.days_before_creation_renewal_invoice'));
            }
        }
        if ($product == null) {
            throw new \Exception('Product not found');
        }
        if ($product->productType()->server() != null) {
            $server = $product->productType()->server()->findServer($product);
            if ($item->configoptions()->where('key', 'server_id')->first() != null) {
                $server = Server::find($item->configoptions()->where('key', 'server_id')->first()->value);
            }
            if ($server != null) {
                $server = $server->id;
            } else {
                $server = null;
            }
        } else {
            $server = null;
        }
        $servicesIds = [];
        $services = [];
        for ($i = 0; $i < $item->quantity; $i++) {
            $service = Service::create([
                'customer_id' => $invoice->customer_id,
                'type' => $product->productType()->uuid(),
                'status' => 'pending',
                'name' => $product->trans('name'),
                'billing' => $item->billing(),
                'product_id' => $product->id,
                'server_id' => $server,
                'invoice_id' => null,
                'expires_at' => $expiresAt,
                'data' => $item->data,
                'currency' => $invoice->currency,
                'trial_ends_at' => $trial_ends_at,
                'max_renewals' => ((int) $product->getMetadata('max_renewals')) ?? null,
            ]);
            $description = '';
            /** @var InvoiceItem $_item */
            foreach ($item->configoptions() as $_item) {
                $configOption = $service->configoptions()->create([
                    'config_option_id' => $_item->related_id,
                    'key' => $_item->data['key'],
                    'value' => $_item->data['value'],
                    'quantity' => $_item->quantity,
                    'expires_at' => $_item->data['expires_at'] != null ? Carbon::createFromFormat('d-m-y H:i', $_item->data['expires_at']) : null,
                ]);
                $description .= $_item->description.' | ';
                $pricing = $_item->relatedType()->getPricing();
                if ($pricing != null) {
                    $pricing->replicate()->fill([
                        'related_id' => $configOption->id,
                        'related_type' => 'config_options_service',
                    ])->save();
                    PricingService::forgot();
                }
            }
            if (! empty($description)) {
                $service->description = $description;
                $service->save();
            }
            if ($item->getDiscount(false)) {
                $service->attachMetadata('discount', json_encode($item->discount));
            }
            if ($item->type == 'free_trial') {
                $service->attachMetadata('free_trial_config', $free_trial->getConfig()->id);
                $service->attachMetadata('free_trial_type', $free_trial->getConfig()->type);
            }
            $servicesIds[] = $service->id;
            $services[] = $service;
            ServiceRenewals::insert([
                'service_id' => $service->id,
                'invoice_id' => $invoice->id,
                'start_date' => Carbon::now(),
                'end_date' => $expiresAt,
                'first_period' => true,
                'next_billing_on' => $next,
                'created_at' => Carbon::now(),
                'period' => 1,
            ]);
        }

        $item->attachMetadata('services', implode(',', $servicesIds));

        return $services;
    }

    /**
     * @throws \Exception
     */
    public static function createInvoiceFromUpgrade(Service $service, Product $newProduct)
    {
        $dto = new UpgradeDTO($service);
        $upgrade = $dto->createUpgrade($newProduct);
        $price = $dto->generatePrice($newProduct);
        $tax = TaxesService::getVatPrice($price->price + $price->setup);
        $days = setting('remove_pending_invoice', 0) != 0 ? setting('remove_pending_invoice') : 7;
        $invoice = Invoice::create([
            'customer_id' => $service->customer_id,
            'due_date' => now()->addDays($days),
            'total' => $price->price + $price->setup + $tax,
            'subtotal' => $price->price + $price->setup,
            'tax' => $tax,
            'setupfees' => $price->setup,
            'currency' => $service->currency,
            'status' => 'pending',
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'notes' => "Upgrade service #{$service->id} ({$service->name}) to {$newProduct->name}",
        ]);
        if (UpgradeDTO::mustForceRenewal($service)) {
            self::appendServiceOnExistingInvoice($service, $invoice, $service->billing, $newProduct->getPriceByCurrency($service->currency, $service->billing));
        }
        $item = $invoice->items()->create([
            'invoice_id' => $invoice->id,
        ] + $dto->toInvoiceItem($newProduct, $upgrade));
        $upgrade->update(['invoice_id' => $invoice->id]);
        $invoice->recalculate();
        event(new InvoiceCreated($invoice));

        return $invoice;
    }

    public static function createInvoiceFromService(Service $service, ?string $billing = null, ?ProductPriceDTO $dto = null)
    {
        $dto = $dto ?? $service->getBillingPrice($billing);
        $price = $service->getBillingPrice($billing)->price;
        $options_prices = $service->getConfigOptionsPrices();
        $billing = $billing ?? $service->billing;
        $currency = $service->currency;
        $tax = TaxesService::getVatPrice($dto->recurringprice_saved + collect($options_prices)->reduce(function (ProductPriceDTO $dto) { return $dto->recurringprice_saved; }, 0));
        $months = app(RecurringService::class)->get($billing)['months'];
        $days = setting('remove_pending_invoice', 0) != 0 ? setting('remove_pending_invoice') : 7;
        $months_label = "{$months} ".__('recurring.month');
        if ($months == 0.5) {
            $months_label = '1 '.__('recurring.week');
        }
        $service_label = sprintf("%s #%d (%s)", $service->product ? $service->product->trans('name') . ' ' : '', $service->id, $service->name);
        $description = __('client.invoices.renewal_description', ['month_label' => $months_label, 'service_label' => $service_label]);
        $invoice = Invoice::create([
            'customer_id' => $service->customer_id,
            'due_date' => now()->addDays($days),
            'total' => $price + $tax,
            'subtotal' => $price,
            'tax' => $tax,
            'setupfees' => 0,
            'currency' => $currency,
            'status' => 'pending',
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'notes' => $description,
        ]);
        $current = $service->expires_at;
        $expiresAt = app(RecurringService::class)->addFrom($service->expires_at, $service->billing);
        $nextBilling = app(RecurringService::class)->addFrom($expiresAt, $service->billing)->subDays(setting('core.services.days_before_creation_renewal_invoice'));
        $item = $invoice->items()->create([
            'invoice_id' => $invoice->id,
            'name' => $service->getInvoiceName(),
            'description' => $description.($service->description != null ? ' | '.$service->description : ''),
            'quantity' => 1,
            'unit_price_ttc' => TaxesService::getPriceWithVat($price),
            'unit_price_ht' => $price,
            'unit_setup_ttc' => 0,
            'unit_setup_ht' => 0,
            'setupfee' => 0,
            'type' => 'renewal',
            'related_id' => $service->id,
            'data' => ['months' => $months, 'billing' => $billing],
            'discount' => $service->getDiscountRenewal(),
        ]);

        foreach ($service->configoptions as $configoption) {
            $dto = new ConfigOptionDTO($configoption->option, $configoption->value, $configoption->expires_at, false);
            if ($dto->needRenewal($service)) {
                self::createConfigOptionItem($configoption, $item, $service->billing, $service->currency);
            }
        }
        ServiceRenewals::insert([
            'service_id' => $service->id,
            'invoice_id' => $invoice->id,
            'start_date' => $current,
            'end_date' => $expiresAt,
            'period' => $service->getAttribute('renewals') + 1,
            'next_billing_on' => $nextBilling,
            'created_at' => Carbon::now(),
        ]);
        event(new InvoiceCreated($invoice));
        $invoice->recalculate();

        return $invoice;
    }

    public static function appendServiceOnExistingInvoice(Service $service, Invoice $invoice, ?string $billing = null, ?ProductPriceDTO $price = null)
    {
        $price = ($price ?? $service->getBillingPrice($billing))->price;
        $months = $service->recurring()['months'];
        $current = $service->expires_at->format('d/m/y');
        $expiresAt = app(RecurringService::class)->addFrom($service->expires_at, $service->billing);
        $nextBilling = app(RecurringService::class)->addFrom($expiresAt, $service->billing)->subDays(setting('core.services.days_before_creation_renewal_invoice'));
        $months_label = "{$months} month";
        if ($months > 1) {
            $months_label .= 's';
        }
        if ($months == 0.5) {
            $months_label = '1 week';
        }
        $item = $invoice->items()->create([
            'invoice_id' => $invoice->id,
            'name' => $service->getInvoiceName(),
            'description' => "Add extra {$months_label} to service #{$service->id} ({$service->name})",
            'quantity' => 1,
            'unit_price_ttc' => TaxesService::getPriceWithVat($price),
            'unit_price_ht' => $price,
            'unit_setup_ttc' => 0,
            'unit_setup_ht' => 0,
            'type' => 'renewal',
            'related_id' => $service->id,
            'data' => ['months' => $months, 'billing' => $service->billing],
            'unit_original_price' => $price,
            'unit_original_setupfees' => 0,
        ]);

        foreach ($service->configoptions as $configoption) {
            $dto = new ConfigOptionDTO($configoption->option, $configoption->value, $configoption->expires_atfalse);
            if ($dto->needRenewal($service)) {
                self::createConfigOptionItem($dto, $item, $service->billing, $service->currency);
            }
        }

        ServiceRenewals::insert([
            'service_id' => $service->id,
            'invoice_id' => $invoice->id,
            'start_date' => $current,
            'end_date' => $expiresAt,
            'period' => $service->getAttribute('renewals') + 1,
            'next_billing_on' => $nextBilling,
            'created_at' => Carbon::now(),
        ]);
        $invoice->recalculate();
    }

    public static function appendProductOnExistingInvoice(AddProductToInvoiceDTO $dto)
    {
        $dto->invoice->items()->create($dto->toArray());
        $dto->invoice->recalculate();
    }

    public static function getBillingType()
    {
        if (setting('billing_mode') == 'invoice') {
            return self::INVOICE;
        }

        return self::PRO_FORMA;
    }

    private static function formatItemname(BasketRow $item): string
    {
        $product = $item->product;
        if ($product == null) {
            return $item->name();
        }
        if ($item->billing == 'onetime') {
            return $product->name;
        }
        $current = Carbon::now();
        $expiresAt = app(RecurringService::class)->addFrom(clone $current, $item->billing);

        return "{$product->trans('name')} ({$current->format('d/m/y')} - {$expiresAt->format('d/m/y')})";
    }

    private static function createInvoiceItemsFromBasket(Basket $basket, Invoice $invoice)
    {
        $basket->items->each(function (BasketRow $item) use ($invoice) {
            $invoiceItem = $invoice->items()->create([
                'invoice_id' => $invoice->id,
                'name' => self::formatItemname($item),
                'description' => 'Created from basket item',
                'quantity' => $item->quantity,
                'unit_price_ht' => $item->recurringPaymentWithoutCouponWithoutOptions(false) + $item->onetimePaymentWithoutCouponWithoutOptions(false),
                'unit_price_ttc' => TaxesService::getPriceWithVat($item->recurringPaymentWithoutCouponWithoutOptions(false) + $item->onetimePaymentWithoutCouponWithoutOptions(false)),
                'unit_setup_ht' => $item->setupWithoutCouponWithoutOptions(false),
                'unit_setup_ttc' => TaxesService::getPriceWithVat($item->setupWithoutCouponWithoutOptions(false)),
                'total' => $item->total(),
                'tax' => $item->tax(),
                'type' => $item->product->productType()->type(),
                'related_id' => $item->product->id,
                'data' => $item->data,
                'discount' => $item->getDiscountArray(),
            ]);
            $billing = $item->billing;
            $currency = $invoice->currency;
            /** @var \App\DTO\Store\ConfigOptionDTO $option */
            foreach ($item->getOptions() as $option) {
                self::createConfigOptionItem($option, $invoiceItem, $billing, $currency);
            }
        });
    }

    private static function createConfigOptionItem(ConfigOptionDTO|ConfigOptionService $option, \Illuminate\Database\Eloquent\Model $invoiceItem, string $billing, string $currency)
    {
        if ($option instanceof ConfigOptionService) {
            $type = 'config_option_service';
            $typeId = $option->id;
            $price = $option->getPriceByCurrency($currency, $billing)->price;
            $setup = 0;
            $option = new ConfigOptionDTO($option->option, $option->value, $option->expires_at, false);
        } else {
            $type = 'config_option';
            $typeId = $option->option->id;
            $price = $option->recurringPayment($currency, $billing, false) + $option->onetimePayment($currency, $billing, false);
            $setup = $option->setup($currency, $billing, false);
        }
        $invoiceItem->invoice->items()->create([
            'invoice_id' => $invoiceItem->invoice->id,
            'name' => $option->getBillingName($currency, $billing),
            'description' => $option->getBillingDescription(),
            'quantity' => $option->quantity(),
            'unit_price_ht' => $price,
            'unit_price_ttc' => TaxesService::getPriceWithVat($price),
            'unit_setup_ht' => $setup,
            'unit_setup_ttc' => TaxesService::getPriceWithVat($setup),
            'total' => $option->total($currency, $billing),
            'tax' => $option->tax($currency, $billing),
            'type' => $type,
            'related_id' => $typeId,
            'data' => $option->data($currency, $billing),
            'parent_id' => $invoiceItem->id,
        ]);
    }
}
