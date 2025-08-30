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
use App\Http\Controllers\Admin\Core\AdminController;
use App\Http\Controllers\Admin\Core\AdminLocalesController;
use App\Http\Controllers\Admin\Core\DashboardController;
use App\Http\Controllers\Admin\Core\EmailController;
use App\Http\Controllers\Admin\Core\MetadataController;
use App\Http\Controllers\Admin\Core\RoleController;
use App\Http\Controllers\Admin\Core\TranslationController;
use App\Http\Controllers\Admin\Settings\SettingsCoreController;
use Illuminate\Support\Facades\Route;

Route::resource('/staffs', AdminController::class)->names('staffs')->except('edit');
Route::get('/profile', [AdminController::class, 'profile'])->name('staffs.profile');
Route::put('/profile', [AdminController::class, 'updateProfile']);
Route::post('/profile/2fa', [AdminController::class, 'save2fa'])->name('profile.2fa');
Route::get('/profile/download_codes', [AdminController::class, 'downloadCodes'])->name('profile.2fa_codes');
Route::resource('/roles', RoleController::class)->names('roles')->except('edit');

Route::resource('/emails', EmailController::class)->names('emails')->except('edit');
Route::get('/preview/emails', [EmailController::class, 'preview'])->name('emails.preview');
Route::get('/intelligent_search', [DashboardController::class, 'intelligentSearch'])->name('intelligent_search');
Route::post('/translations/settings', [TranslationController::class, 'storeSettingsTranslations'])->name('translations.settings');
Route::post('/translations', [TranslationController::class, 'storeTranslations'])->name('translations.index');
Route::get('/locales', [AdminLocalesController::class, 'index'])->name('locales.index');

Route::post('/locales/download/{locale}', [AdminLocalesController::class, 'download'])->name('locales.download');
Route::post('/locales/toggle/{locale}', [AdminLocalesController::class, 'toggle'])->name('locales.toggle');
Route::put('/metadata', [MetadataController::class, 'update'])->name('metadata.update');

Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::put('/core/email', [SettingsCoreController::class, 'storeEmailSettings'])->name('core.email');
    Route::put('/core/app', [SettingsCoreController::class, 'storeAppSettings'])->name('core.app');
    Route::put('/core/maintenance', [SettingsCoreController::class, 'storeMaintenanceSettings'])->name('core.maintenance');
});
