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
use App\Http\Controllers\Admin\Settings\SettingsProvisioningController;
use App\Models\Admin\Permission;
use App\Models\Provisioning\Service;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class ProvisioningServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! is_installed()) {
            return;
        }
        $this->registerWidgets();
    }

    private function registerWidgets()
    {
        $customerWidgets = new AdminCountWidget('customers', 'bi bi-person-vcard', 'admin.customers.title', function () {
            return Service::countCustomers();
        }, 'admin.manage_customers');
        $this->app['extension']->addAdminCountWidget($customerWidgets);
        $serviceWidgets = new AdminCountWidget('services', 'bi bi-boxes', 'provisioning.active_services', function () {
            return Service::where('status', 'active')->count();
        }, 'admin.manage_services');
        $this->app['extension']->addAdminCountWidget($serviceWidgets);
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('services_canvas', function () {
            $data = Service::selectRaw('count(*) as count, status')->groupBy('status')->get();
            $dto = new \App\DTO\Admin\Dashboard\ServiceStatesCanvaDTO($data->toArray());

            return view('admin.dashboard.cards.services-canvas', ['dto' => $dto]);
        }, 'admin.show_services', 1));
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('services', function () {
            $services = Service::where('status', 'active')->where('expires_at', '<=', \Carbon\Carbon::now()->addDays(setting('core.services.days_before_expiration', 7)))->limit(3)->get();

            return view('admin.dashboard.cards.services-expiration', ['services' => $services]);
        }, 'admin.show_services', 2));

        /** @var SettingsService $setting */
        $setting = app('settings');

        $setting->addCard('provisioning', 'provisioning.admin.title', 'provisioning.admin.subheading', 2, null, true, 2, 'bi bi-boxes');
        $setting->addCardItem('provisioning', 'services', 'provisioning.admin.settings.services.title', 'provisioning.admin.settings.services.description', 'bi bi-box2', [SettingsProvisioningController::class, 'showServicesSettings'], Permission::MANAGE_SETTINGS);
        $setting->addCardItem('provisioning', 'servers', 'provisioning.admin.servers.title', 'provisioning.admin.servers.subheading', 'bi bi-hdd-rack', route('admin.servers.index'), 'admin.manage_servers');
        $setting->addCardItem('provisioning', 'subdomains_hosts', 'provisioning.admin.subdomains_hosts.title', 'provisioning.admin.subdomains_hosts.subheading', 'bi bi-list-stars', route('admin.subdomains_hosts.index'), 'admin.manage_subdomains_hosts');
        $setting->addCardItem('provisioning', 'configoptions_services', 'provisioning.admin.configoptions_services.title', 'provisioning.admin.configoptions_services.subheading', 'bi bi-boxes', route('admin.configoptions_services.index'), true);
        $setting->addCardItem('provisioning', 'configoptions', 'provisioning.admin.configoptions.title', 'provisioning.admin.configoptions.subheading', 'bi bi-cart-plus', route('admin.configoptions.index'), true);
        $setting->addCardItem('provisioning', 'cancellation_reasons', 'provisioning.admin.cancellation_reasons.title', 'provisioning.admin.cancellation_reasons.subheading', 'bi bi-x-circle', action([\App\Http\Controllers\Admin\Provisioning\CancellationReasonController::class, 'index']), 'admin.manage_services');
        \View::share('store_groups', \Cache::remember('store_groups', 3600 * 24 * 7, function () {
            try {
                return \App\Models\Store\Group::getAvailable()->orderBy('sort_order')->orderBy('pinned')->get();
            } catch (\Exception $e) {
                return collect();
            }
        }));
    }
}
