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

use App\Core\Admin\Dashboard\AdminCardWidget;
use App\Core\Admin\Dashboard\AdminCountWidget;
use App\Core\Menu\AdminMenuItem;
use App\Http\Controllers\Admin\Billing\SubscriptionController;
use App\Http\Controllers\Admin\Billing\UpgradeController;
use App\Http\Controllers\Admin\Settings\SettingsBillingController;
use App\Models\Account\Customer;
use App\Models\Admin\Permission;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! is_installed() || app()->runningUnitTests() || app()->runningInConsole()) {
            return;
        }
        $invoiceWidgets = new AdminCountWidget('invoices', 'bi bi-receipt-cutoff', 'admin.invoices.title', function () {
            return Invoice::count();
        }, 'admin.manage_invoices');
        $this->app['extension']->addAdminCountWidget($invoiceWidgets);
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('services', 'admin.services.index', 'bi bi-box2', 'provisioning.admin.services.title', 3, 'admin.show_services')));
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('invoices', 'admin.invoices.index', 'bi bi-receipt-cutoff', 'admin.invoices.title', 4, 'admin.show_invoices')));
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('best_products', function () {
            $dto = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProducts();
            $week = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastWeek();
            $month = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastMonth();

            return view('admin.dashboard.cards.best-selling', compact('dto', 'week', 'month'));
        }, 'admin.earn_page', 2));

        $subscriptions = function () {
            return Subscription::where('state', 'active')->count();
        };
        $subscriptionWidgets = new AdminCountWidget('subscriptions', 'bi bi-credit-card-2-front', 'billing.admin.subscriptions.active_subscription', $subscriptions, 'admin.manage_services');
        $this->app['extension']->addAdminCountWidget($subscriptionWidgets);
        $balanceWidgets = new AdminCountWidget('global_balance', 'bi bi-cash', 'billing.admin.global_balance', function () {
            return formatted_price(Customer::sum('balance'));
        }, 'admin.manage_customers', true);
        $this->app['extension']->addAdminCountWidget($balanceWidgets);

        $this->app['settings']->addCard('billing', 'billing.admin.title', 'billing.admin.subheading', 4, null, true, 2, 'bi bi-credit-card-2-front');
        $this->app['settings']->addCardItem('billing', 'subscriptions', 'billing.admin.subscriptions.title', 'billing.admin.subscriptions.description', 'bi bi-credit-card-2-front', action([SubscriptionController::class, 'index']), 'admin.manage_invoices');
        $this->app['settings']->addCardItem('billing', 'billing', 'billing.admin.settings.title', 'billing.admin.settings.description', 'bi bi-basket2-fill', [SettingsBillingController::class, 'showBilling'], Permission::MANAGE_SETTINGS);
        $this->app['settings']->addCardItem('billing', 'upgrades', 'billing.admin.upgrades.title', 'billing.admin.upgrades.description', 'bi bi-arrows-angle-expand', action([UpgradeController::class, 'index']), 'admin.manage_services');
        $this->app['extension']->addFrontMenuItem((new \App\Core\Menu\FrontMenuItem('payment-methods', 'front.payment-methods.index', 'bi bi-credit-card', 'client.payment-methods.index', 5)));
        $this->app['extension']->addFrontMenuItem((new \App\Core\Menu\FrontMenuItem('invoices', 'front.invoices.index', 'bi bi-receipt', 'client.invoices.index', 3)));
    }
}
