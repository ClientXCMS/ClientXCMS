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

use App\Http\Controllers\Admin\Provisioning\CancellationReasonController;
use App\Http\Controllers\Admin\Provisioning\ServerController;
use App\Http\Controllers\Admin\Provisioning\ServiceController;
use App\Http\Controllers\Admin\Provisioning\SubdomainHostController;
use Illuminate\Support\Facades\Route;

Route::resource('/cancellation-reasons', CancellationReasonController::class)->names('cancellation_reasons')->except('edit');
Route::resource('/subdomains-hosts', SubdomainHostController::class)->names('subdomains_hosts')->except('edit');
if (config('features.domain_management')) {
    Route::prefix('domain-tlds/tools')->name('domain_tlds.tools.')->controller(\App\Http\Controllers\Admin\Provisioning\DomainTldToolsController::class)->group(function () {
        Route::post('/catalog', 'catalog')->name('catalog');
        Route::post('/copy', 'copy')->name('copy');
        Route::get('/operations/{operation}', 'operation')->name('operation');
        Route::post('/operations/{operation}/preview', 'previewImport')->name('preview');
        Route::post('/operations/{operation}/apply', 'apply')->name('apply');
        Route::post('/dns/{service}/retry', 'retryDns')->name('retry-dns');
    });
    Route::resource('/domain-tlds', \App\Http\Controllers\Admin\Provisioning\DomainTldController::class)->names('domain_tlds')->except('edit');
}
Route::get('/upgrades', [\App\Http\Controllers\Admin\Billing\UpgradeController::class, 'index'])->name('upgrades.index');
Route::resource('/servers', ServerController::class)->names('servers')->except('edit');
Route::get('/testservers', [ServerController::class, 'test'])->name('servers.test');
Route::resource('/services', ServiceController::class)->names('services')->except('edit');
Route::post('/services/mass-action', [ServiceController::class, 'massAction'])->name('services.mass_action');
Route::post('/services/{service}/renew', [ServiceController::class, 'renew'])->name('services.renew');
Route::post('/services/{service}/delivery', [ServiceController::class, 'delivery'])->name('services.delivery');
Route::post('/services/{service}/subscription', [ServiceController::class, 'subscription'])->name('services.subscription');
Route::post('/services/{service}/reinstall', [ServiceController::class, 'reinstall'])->name('services.reinstall');
Route::post('/services/{service}/update-data', [ServiceController::class, 'updateData'])->name('services.update_data');
Route::post('/services/{service}/upgrade', [ServiceController::class, 'upgrade'])->name('services.upgrade');
Route::get('/services/{service}/{tab}', [ServiceController::class, 'tab'])->name('services.tab');
Route::post('/services/{service}/action/{action}', [ServiceController::class, 'changeStatus'])->name('services.action')->where('action', 'suspend|unsuspend|expire|cancel|cancel_delivery');
Route::resource('/configoptions-services', \App\Http\Controllers\Admin\Provisioning\ConfigOptionServiceController::class)->names('configoptions_services')->except('edit');

Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::put('/provisioning/services', [\App\Http\Controllers\Admin\Settings\SettingsProvisioningController::class, 'storeServicesSettings'])->name('provisioning.services');
});
