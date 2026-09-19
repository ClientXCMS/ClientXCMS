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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Providers;

use App\Contracts\Billing\ElectronicInvoiceRendererInterface;
use App\Contracts\Billing\EReportingScheduleInterface;
use App\Contracts\Billing\PaymentLedgerRecorderInterface;
use App\Core\Admin\Dashboard\AdminCountWidget;
use App\Core\Menu\AdminMenuItem;
use App\Http\Controllers\Admin\Billing\ElectronicInvoicingController;
use App\Http\Controllers\Admin\Billing\SubscriptionController;
use App\Http\Controllers\Admin\Billing\UpgradeController;
use App\Http\Controllers\Admin\Settings\SettingsBillingController;
use App\Models\Account\Customer;
use App\Models\Admin\Permission;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Services\Billing\AccountingProviderRegistry;
use App\Services\Billing\ElectronicProviderRegistry;
use App\Services\Billing\EReportingScheduleService;
use App\Services\Billing\FacturXRenderer;
use App\Services\Billing\FiscalProfileExtensionRegistry;
use App\Services\Billing\LocalElectronicExchangeProvider;
use App\Services\Billing\PaymentLedgerService;
use App\Services\Core\PaymentTypeService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentTypeService::class);
        $this->app->singleton(FiscalProfileExtensionRegistry::class);
        $this->app->singleton(ElectronicProviderRegistry::class);
        $this->app->singleton(AccountingProviderRegistry::class);
        $this->app->singleton(LocalElectronicExchangeProvider::class);
        $this->app->bind(ElectronicInvoiceRendererInterface::class, FacturXRenderer::class);
        $this->app->bind(EReportingScheduleInterface::class, EReportingScheduleService::class);
        $this->app->bind(PaymentLedgerRecorderInterface::class, PaymentLedgerService::class);
        $this->app->booted(function () {
            $extension = $this->app['extension'];
            $extension->addInvoiceItem(new \App\Billing\Items\ProductInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\RenewalInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\CustomInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\ConfigOptionInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\ConfigOptionServiceInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\UpgradeInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\AddFundInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\FreeTrialInvoiceItem);
            $extension->addInvoiceItem(new \App\Billing\Items\GiftCardInvoiceItem);
        });
    }

    public function boot(): void
    {
        $this->app->make(ElectronicProviderRegistry::class)->register($this->app->make(LocalElectronicExchangeProvider::class));
        $this->registerElectronicInvoicingListeners();

        if (! is_installed() || app()->runningUnitTests() || app()->runningInConsole()) {
            return;
        }
        $invoiceWidgets = new AdminCountWidget('invoices', 'bi bi-receipt-cutoff', 'admin.invoices.title', function () {
            return Invoice::count();
        }, 'admin.manage_invoices');
        $this->app['extension']->addAdminCountWidget($invoiceWidgets);
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('services', 'admin.services.index', 'bi bi-box2', 'provisioning.admin.services.title', 3, 'admin.show_services')));
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('invoices', 'admin.invoices.index', 'bi bi-receipt-cutoff', 'admin.invoices.title', 4, 'admin.show_invoices')));
        // $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('best_products', function () {
        //     $dto = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProducts();
        //     $week = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastWeek();
        //     $month = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastMonth();

        //     return view('admin.dashboard.cards.best-selling', compact('dto', 'week', 'month'));
        // }, 'admin.earn_page', 2));

        $subscriptions = function () {
            return Subscription::where('state', 'active')->count();
        };
        $extension = $this->app['extension'];
        $subscriptionWidgets = new AdminCountWidget('subscriptions', 'bi bi-credit-card-2-front', 'billing.admin.subscriptions.active_subscription', $subscriptions, 'admin.manage_services');
        $extension->addAdminCountWidget($subscriptionWidgets);
        $balanceWidgets = new AdminCountWidget('global_balance', 'bi bi-cash', 'billing.admin.global_balance', function () {
            return formatted_price(Customer::sum('balance'));
        }, 'admin.manage_customers', true);
        $extension->addAdminCountWidget($balanceWidgets);

        $extension->addInvoiceItem(new \App\Billing\Items\ProductInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\RenewalInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\CustomInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\ConfigOptionInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\ConfigOptionServiceInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\UpgradeInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\AddFundInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\FreeTrialInvoiceItem);
        $extension->addInvoiceItem(new \App\Billing\Items\GiftCardInvoiceItem);
        $this->app['settings']->addCard('billing', 'billing.admin.title', 'billing.admin.subheading', 4, null, true, 2, 'bi bi-credit-card-2-front');
        $this->app['settings']->addCardItem('billing', 'subscriptions', 'billing.admin.subscriptions.title', 'billing.admin.subscriptions.description', 'bi bi-credit-card-2-front', action([SubscriptionController::class, 'index']), 'admin.manage_invoices');
        $this->app['settings']->addCardItem('billing', 'billing', 'billing.admin.settings.title', 'billing.admin.settings.description', 'bi bi-basket2-fill', [SettingsBillingController::class, 'showBilling'], Permission::MANAGE_SETTINGS);
        $this->app['settings']->addCardItem('billing', 'electronic_invoicing', 'einvoicing.admin.tab', 'einvoicing.admin.description', 'bi bi-file-earmark-code', [ElectronicInvoicingController::class, 'index'], Permission::MANAGE_SETTINGS);
        $this->app['settings']->addCardItem('billing', 'upgrades', 'billing.admin.upgrades.title', 'billing.admin.upgrades.description', 'bi bi-arrows-angle-expand', action([UpgradeController::class, 'index']), 'admin.manage_services');
        $extension->addFrontMenuItem((new \App\Core\Menu\FrontMenuItem('payment-methods', 'front.payment-methods.index', 'bi bi-credit-card', 'client.payment-methods.index', 5)));
        $extension->addFrontMenuItem((new \App\Core\Menu\FrontMenuItem('invoices', 'front.invoices.index', 'bi bi-receipt', 'client.invoices.index', 3)));
    }

    private function registerElectronicInvoicingListeners(): void
    {
        Event::listen(\App\Events\Core\Invoice\InvoiceIssued::class, function ($event) {
            $invoice = $event->invoice;
            foreach (app(AccountingProviderRegistry::class)->enabled() as $provider) {
                \App\Jobs\Billing\ExportAccountingDocument::dispatch($invoice, $provider->key());
            }
            if (! $this->electronicInvoicingApplies($invoice->issued_at)) {
                return;
            }
            $routing = data_get($invoice->billing_snapshot, 'tax.electronic_routing', app(\App\Services\Billing\FiscalProfileService::class)->electronicRouting($invoice->customer));
            if ($routing === \App\Services\Billing\FiscalProfileService::ROUTING_EINVOICING) {
                \App\Jobs\Billing\SubmitElectronicInvoice::dispatch($invoice);
            } elseif ($routing === \App\Services\Billing\FiscalProfileService::ROUTING_EREPORTING) {
                app(\App\Services\Billing\EReportingService::class)->recordInvoice($invoice);
            } else {
                $hash = hash('sha256', json_encode($invoice->billing_snapshot, JSON_THROW_ON_ERROR));
                \App\Models\Billing\ElectronicDocument::firstOrCreate([
                    'idempotency_key' => \App\Models\Billing\ElectronicDocument::idempotencyKey($invoice, 'manual', $hash),
                ], [
                    'documentable_type' => $invoice::class, 'documentable_id' => $invoice->id, 'provider' => 'manual',
                    'format' => 'manual-review', 'status' => \App\Models\Billing\ElectronicDocument::STATUS_FAILED,
                    'payload_sha256' => $hash, 'last_error_code' => 'manual_review',
                    'last_error_message' => 'Le profil fiscal exige une revue manuelle avant transmission.',
                ]);
            }
        });
        Event::listen(\App\Events\Core\Invoice\InvoiceCompleted::class, function ($event) {
            $invoice = $event->invoice;
            app(PaymentLedgerRecorderInterface::class)->record($invoice, 'payment', (string) $invoice->total, $invoice->paid_at ?? now(), 'invoice-paid:'.$invoice->uuid, $invoice->external_id);
        });
        Event::listen(\App\Events\Core\Invoice\PaymentTransactionRecorded::class, function ($event) {
            if ($this->electronicInvoicingApplies($event->transaction->occurred_at)) {
                app(\App\Services\Billing\EReportingService::class)->recordPayment($event->transaction);
            }
            foreach (app(AccountingProviderRegistry::class)->enabled() as $provider) {
                \App\Jobs\Billing\ExportAccountingPayment::dispatch($event->transaction, $provider->key());
            }
        });
        Event::listen(\App\Events\Core\Invoice\InvoiceRefunded::class, function ($event) {
            $invoice = $event->invoice;
            app(PaymentLedgerRecorderInterface::class)->record($invoice, 'refund', (string) $invoice->total, now(), 'invoice-refund:'.$invoice->uuid.':'.$invoice->updated_at?->timestamp);
        });
        Event::listen('eloquent.created: '.\App\Models\Billing\CreditNote::class, function ($creditNote) {
            foreach (app(AccountingProviderRegistry::class)->enabled() as $provider) {
                \App\Jobs\Billing\ExportAccountingDocument::dispatch($creditNote, $provider->key());
            }
            if (! $this->electronicInvoicingApplies($creditNote->created_at)) {
                return;
            }
            if (data_get($creditNote->invoice->billing_snapshot, 'tax.electronic_routing') === \App\Services\Billing\FiscalProfileService::ROUTING_EINVOICING) {
                \App\Jobs\Billing\SubmitElectronicInvoice::dispatch($creditNote);
            }
        });
    }

    private function electronicInvoicingApplies($date): bool
    {
        if (! filter_var(setting('einvoicing_enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }
        $activation = setting('einvoicing_activation_date');

        return blank($activation) || ($date && $date->gte(\Carbon\CarbonImmutable::parse($activation)->startOfDay()));
    }
}
