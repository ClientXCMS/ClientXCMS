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

use App\Http\Controllers\Admin\Core\DashboardController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\SettingsCoreController;
use App\Http\Controllers\Admin\Settings\SettingsExtensionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', admin_prefix('dashboard'));
Route::fallback(function () {
    return response()->view('admin.errors.404', [], 404);
});
Route::get('/darkmode', [\App\Http\Controllers\DarkModeController::class, 'darkmodeAdmin'])->name('darkmode.switch');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('/{card}/{uuid}', [SettingsController::class, 'show'])->name('show');
    Route::get('/testmail', [SettingsCoreController::class, 'testmail'])->name('testmail');
    Route::post('/extensions/{type}/{extension}/enable', [SettingsExtensionController::class, 'enable'])->name('extensions.enable');
    Route::post('/extensions/{type}/{extension}/disable', [SettingsExtensionController::class, 'disable'])->name('extensions.disable');
    Route::post('/extensions/{type}/{extension}/update', [SettingsExtensionController::class, 'update'])->name('extensions.update');
    Route::post('/extensions/{type}/{extension}/uninstall', [SettingsExtensionController::class, 'uninstall'])->name('extensions.uninstall');
    Route::post('/extensions/install/{type}/{uuid}', [SettingsExtensionController::class, 'install'])->name('extensions.install');
    Route::get('/extensions', [SettingsExtensionController::class, 'showExtensions'])->name('extensions.index');
    Route::post('/extensions/clear', [SettingsExtensionController::class, 'clear'])->name('extensions.clear');
    Route::post('/extensions/clear-cache', [SettingsExtensionController::class, 'clearCache'])->name('extensions.clear-cache');
});

require __DIR__.'/admin/auth.php';
require __DIR__.'/admin/personalization.php';
require __DIR__.'/admin/provisioning.php';
require __DIR__.'/admin/billing.php';
require __DIR__.'/admin/customers.php';
require __DIR__.'/admin/helpdesk.php';
require __DIR__.'/admin/store.php';
require __DIR__.'/admin/core.php';
require __DIR__.'/admin/security.php';
