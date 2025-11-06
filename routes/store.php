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
use App\Http\Controllers\Front\Store\Basket\BasketController;
use App\Http\Controllers\Front\Store\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('/store')->name('front.')->group(function () {
    Route::get('/', [StoreController::class, 'index'])->name('store.index');

    Route::prefix('/basket')->name('store.basket.')->group(function () {
        Route::get('/', [BasketController::class, 'show'])->name('show');
        Route::get('/checkout', [BasketController::class, 'showCheckout'])->name('checkout');
        Route::post('/checkout', [BasketController::class, 'processCheckout']);
        Route::post('/coupon', [BasketController::class, 'coupon'])->name('coupon');
        Route::delete('/coupon', [BasketController::class, 'removeCoupon'])->name('coupon.remove');
        Route::any('/add/{product}', [BasketController::class, 'addProduct'])->name('add');
        Route::get('/config/{product}', [BasketController::class, 'showConfigProduct'])->name('config');
        Route::post('/config/{product}', [BasketController::class, 'configProduct']);
        Route::delete('/remove/{product}', [BasketController::class, 'removeRow'])->name('remove');
        Route::post('/quantity/{product}', [BasketController::class, 'changeQuantity'])->name('quantity');
    });

    Route::get('/{group:slug}', [StoreController::class, 'group'])->name('store.group');
    Route::get('/{group:slug}/{subgroup:slug}', [StoreController::class, 'subgroup'])->name('store.subgroup');
});
