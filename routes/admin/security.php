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

use App\Http\Controllers\Admin\Security\ActionsLogController;
use App\Http\Controllers\Admin\Security\DatabaseController;
use App\Http\Controllers\Admin\Security\HistoryController;
use App\Http\Controllers\Admin\Security\LicenseController;
use App\Http\Controllers\Admin\Security\SecurityQuestionController;
use App\Http\Controllers\Admin\Settings\SettingsSecurityController;
use App\Http\Controllers\Admin\Security\UpdateController;
use Illuminate\Support\Facades\Route;

Route::name('history.')->prefix('history')->group(function () {
    Route::get('/', [HistoryController::class, 'index'])->name('index');
    Route::get('/download', [HistoryController::class, 'download'])->name('download');
});
Route::get('/database', [DatabaseController::class, 'index'])->name('database.index');
Route::post('/database', [DatabaseController::class, 'migrate']);
Route::get('/update', [UpdateController::class, 'index'])->name('update.index');
Route::post('/update', [UpdateController::class, 'update'])->name('update');

Route::resource('/logs', ActionsLogController::class)->names('logs')->except('edit', 'update', 'delete', 'create', 'store');
Route::get('/license', [LicenseController::class, 'index'])->name('license.index')->middleware('password.confirm:admin.password.confirm');
Route::get('/api-keys', [\App\Http\Controllers\Admin\Security\ApiKeysController::class, 'index'])->name('api-keys.index')->middleware('password.confirm:admin.password.confirm');
Route::get('/api-keys/create', [\App\Http\Controllers\Admin\Security\ApiKeysController::class, 'create'])->name('api-keys.create')->middleware('password.confirm:admin.password.confirm');
Route::post('/api-keys', [\App\Http\Controllers\Admin\Security\ApiKeysController::class, 'store'])->name('api-keys.store')->middleware('password.confirm:admin.password.confirm');
Route::delete('/api-keys/{apiKey}', [\App\Http\Controllers\Admin\Security\ApiKeysController::class, 'destroy'])->name('api-keys.destroy')->middleware('password.confirm:admin.password.confirm');
Route::put('/api-keys/{apiKey}', [\App\Http\Controllers\Admin\Security\ApiKeysController::class, 'rotate'])->name('api-keys.rotate')->middleware('password.confirm:admin.password.confirm');
Route::name('settings.')->prefix('settings')->group(function () {
    Route::put('/security/security', [SettingsSecurityController::class, 'storeSecurity'])->name('security.security');
});
Route::resource('/security_questions', SecurityQuestionController::class)->names('security_questions')->except('edit');
