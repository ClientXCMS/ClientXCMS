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

use App\Http\Controllers\Front\Billing\PaymentMethodController;
use App\Http\Controllers\Front\ClientController;
use App\Http\Controllers\Front\EmailController;
use App\Http\Controllers\Front\SubUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/client')->name('front.')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->middleware(['auth'])->name('client.index');
    Route::get('/onboarding', [ClientController::class, 'onboarding'])->middleware(['auth'])->name('client.onboarding');

    Route::prefix('/profile/subusers')->name('subusers.')->middleware(['auth'])->group(function () {
        Route::get('/', [SubUserController::class, 'index'])->name('index');
        Route::post('/', [SubUserController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/invitations/{token}', [SubUserController::class, 'showAccept'])->name('accept')->withoutMiddleware('auth');
        Route::post('/invitations/{token}', [SubUserController::class, 'accept'])->middleware('throttle:6,1')->name('accept.confirm')->withoutMiddleware('auth');
        Route::put('/accesses/{access}', [SubUserController::class, 'update'])->name('accesses.update');
        Route::delete('/accesses/{access}', [SubUserController::class, 'destroy'])->name('accesses.destroy');
        Route::post('/invitations/{invitation}/resend', [SubUserController::class, 'resend'])->middleware('throttle:5,1')->name('invitations.resend');
        Route::delete('/invitations/{invitation}', [SubUserController::class, 'revoke'])->name('invitations.revoke');
    });

    Route::prefix('/profile')->name('profile')->middleware(['auth'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Front\ProfileController::class, 'show'])->name('.index');
        Route::post('/', [\App\Http\Controllers\Front\ProfileController::class, 'update'])->name('.update');
        Route::post('/password', [\App\Http\Controllers\Front\ProfileController::class, 'password'])->name('.password');
        // GDPR export: cap to 3 builds per day per user (heavy: zips every invoice PDF).
        Route::post('/export', [\App\Http\Controllers\Front\ProfileController::class, 'export'])
            ->middleware('throttle:3,1440')
            ->name('.export');
        // v2.16 — signed download endpoint used by the link returned by export()
        Route::get('/export/download/{path}', [\App\Http\Controllers\Front\ProfileController::class, 'downloadExport'])
            ->where('path', '.*')
            ->name('.export.download')
            ->middleware('signed');
        Route::post('/2fa', [\App\Http\Controllers\Front\ProfileController::class, 'save2fa'])->name('.2fa');
        Route::post('/2fa/options', [\App\Http\Controllers\Front\ProfileController::class, 'save2faOptions'])->name('.2fa_options');
        Route::post('/2fa/trusted/revoke', [\App\Http\Controllers\Front\ProfileController::class, 'revokeTrustedDevice'])->name('.2fa_trusted_revoke');
        Route::post('/2fa/trusted/revoke-all', [\App\Http\Controllers\Front\ProfileController::class, 'revokeAllTrustedDevices'])->name('.2fa_trusted_revoke_all');
        Route::get('/download_codes', [\App\Http\Controllers\Front\ProfileController::class, 'downloadCodes'])->name('.2fa_codes');
        Route::delete('/delete', [\App\Http\Controllers\Front\ProfileController::class, 'deleteAccount'])->name('.delete.confirm');
        Route::post('/security-question', [\App\Http\Controllers\Front\ProfileController::class, 'saveSecurityQuestion'])->name('.security_question');
    });
    Route::prefix('/emails')->name('emails.')->group(function () {
        Route::get('/', [EmailController::class, 'index'])->middleware(['auth', 'verified'])->name('index');
        Route::get('/read-all', [EmailController::class, 'readAll'])->middleware(['auth', 'verified'])->name('read-all');
        Route::get('/{email}', [EmailController::class, 'show'])->middleware(['auth', 'verified'])->name('show');
    });
    Route::prefix('/payment-methods')->name('payment-methods.')->group(function () {
        Route::post('/{gateway}/add', [PaymentMethodController::class, 'add'])->middleware(['auth'])->name('add');
        Route::get('/', [PaymentMethodController::class, 'index'])->middleware(['auth'])->name('index');
        Route::post('/default/{paymentMethod}', [PaymentMethodController::class, 'default'])->name('default');
        Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'delete'])->middleware(['auth'])->name('delete');
        Route::post('/pay/{invoice}', [PaymentMethodController::class, 'pay'])->middleware(['auth'])->name('pay');
    });
});
