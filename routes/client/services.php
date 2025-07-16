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
use App\Http\Controllers\Front\Provisioning\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('/client')->name('front.')->group(function () {
    Route::prefix('/services')->name('services')->middleware(['auth'])->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('.index');
        Route::get('/{service}', [ServiceController::class, 'show'])->name('.show');
        Route::get('/{service}/upgrade', [ServiceController::class, 'upgrade'])->name('.upgrade');
        Route::get('/{service}/options', [ServiceController::class, 'options'])->name('.options');
        Route::get('/{service}/upgrade/{product}', [ServiceController::class, 'upgradeProcess'])->name('.upgrade_process');
        Route::get('/billing/{service}', [ServiceController::class, 'renewal'])->name('.renewal');
        Route::post('/billing/{service}', [ServiceController::class, 'billing'])->name('.billing');
        Route::post('/name/{service}', [ServiceController::class, 'name'])->name('.name');
        Route::post('/cancel/{service}', [ServiceController::class, 'cancel'])->name('.cancel');
        Route::get('/tab/{service}/{tab}', [ServiceController::class, 'tab'])->name('.tab');
        Route::get('/{service}/renew/{gateway}', [ServiceController::class, 'renew'])->name('.renew');
        Route::post('/subscription/{service}', [ServiceController::class, 'subscription'])->name('.subscription');
    });
});
