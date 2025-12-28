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

use App\Http\Controllers\Admin\Store\CouponController;
use App\Http\Controllers\Admin\Store\GatewayController;
use App\Http\Controllers\Admin\Store\GroupController;
use App\Http\Controllers\Admin\Store\ProductController;
use App\Models\Billing\Gateway;
use App\Services\SettingsService;
use App\Services\Store\CurrencyService;
use App\Services\Store\ProductTypeService;
use App\Services\Store\RecurringService;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RecurringService::class);
        $this->app->singleton(CurrencyService::class);
        $this->app->singleton(ProductTypeService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! is_installed() || app()->runningUnitTests() || app()->runningInConsole()) {
            return;
        }
        try {
            $gateways = Gateway::all();
        } catch (\Exception $e) {
            $gateways = [];
        }
        /** @var SettingsService $setting */
        $setting = app('settings');
        $setting->addCard('store', 'admin.settings.store.title', 'admin.settings.store.description', 2, null, true, 2, 'bi bi-shop-window');
        $setting->addCardItem('store', 'product', 'admin.products.title', 'admin.products.description', 'bi bi-box', action([ProductController::class, 'index']), 'admin.manage_products');
        $setting->addCardItem('store', 'group', 'admin.groups.title', 'admin.groups.description', 'bi bi-shop', action([GroupController::class, 'index']), 'admin.manage_groups');
        $setting->addCardItem('store', 'coupon', 'coupon.coupons', 'coupon.admin.description', 'bi bi-percent', action([CouponController::class, 'index']), 'admin.manage_coupons');

        foreach ($gateways as $gateway) {
            if ($gateway->uuid == 'none' || $gateway->paymentType() == null) {
                continue;
            }
            $setting->addCardItem('store', $gateway->uuid, $gateway->name, 'admin.settings.store.gateways.description', $gateway->paymentType()->icon(), [GatewayController::class, 'config'], 'admin.manage_gateways');
        }
    }
}
