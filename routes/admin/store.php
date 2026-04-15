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
use App\Http\Controllers\Admin\Store\CouponController;
use App\Http\Controllers\Admin\Store\GatewayController;
use App\Http\Controllers\Admin\Store\GroupController;
use App\Http\Controllers\Admin\Store\ProductController;
use Illuminate\Support\Facades\Route;

Route::resource('/groups', GroupController::class)->names('groups')->except('edit');
Route::post('/groups/sort', [GroupController::class, 'sort'])->name('groups.sort')->withoutMiddleware('csrf');
Route::put('/groups/{group}/clone', [GroupController::class, 'clone'])->name('groups.clone');
Route::resource('/products', ProductController::class)->names('products')->except('edit');
Route::post('/products/{product}/config', [ProductController::class, 'config'])->name('products.config');
Route::put('/products/{product}/clone', [ProductController::class, 'clone'])->name('products.clone');
Route::delete('/coupons/usage/{coupon_usage}', [CouponController::class, 'deleteUsage'])->name('coupons.deleteusage');
Route::resource('/coupons', CouponController::class)->names('coupons')->except('edit');
Route::resource('/configoptions', \App\Http\Controllers\Admin\Provisioning\ConfigOptionController::class)->names('configoptions')->except('edit');
Route::put('/configoptions/{config_option}/options', [\App\Http\Controllers\Admin\Provisioning\ConfigOptionController::class, 'storeOptions'])->name('configoptions.add_option');
Route::post('/configoptions/{config_option}/options', [\App\Http\Controllers\Admin\Provisioning\ConfigOptionController::class, 'updateOptions'])->name('configoptions.update_options');
Route::delete('/configoptions/{config_option}/options/{option}', [\App\Http\Controllers\Admin\Provisioning\ConfigOptionController::class, 'destroyOption'])->name('configoptions.destroy_option');

Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::put('/store/gateway/{gateway}', [GatewayController::class, 'saveConfig'])->name('store.gateways.save');
});
