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
use App\Http\Controllers\Front\Helpdesk\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('/client')->name('front.')->group(function () {
    Route::prefix('/support')->name('support')->middleware('auth')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('.index');
        Route::get('/create', [SupportController::class, 'create'])->name('.create');
        Route::post('/create', [SupportController::class, 'store'])->middleware('throttle:6,1');
        Route::delete('/{ticket}/close', [SupportController::class, 'close'])->name('.close');
        Route::post('/{ticket}/reopen', [SupportController::class, 'reopen'])->name('.reopen');
        Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('.reply')->middleware('throttle:6,1');
        Route::get('/{ticket}', [SupportController::class, 'show'])->name('.show');
        Route::post('/{ticket}/messages/{message}/update', [SupportController::class, 'updateMessage'])->name('.messages.update')->middleware('throttle:6,1');
        Route::delete('/{ticket}/messages/{message}/delete', [SupportController::class, 'destroyMessage'])->name('.messages.destroy')->middleware('throttle:6,1');
        Route::get('/{ticket}/download/{attachment}', [SupportController::class, 'download'])->name('.download');
    });
});
