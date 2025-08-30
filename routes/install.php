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

use App\Http\Controllers\InstallController;

Route::get('/settings', [InstallController::class, 'showSettings'])->name('settings');
Route::post('/settings', [InstallController::class, 'storeSettings']);
Route::get('/register', [InstallController::class, 'showRegister'])->name('register');
Route::post('/register', [InstallController::class, 'storeRegister']);
Route::get('/summary', [InstallController::class, 'showSummary'])->name('summary');
Route::post('/summary', [InstallController::class, 'storeSummary']);