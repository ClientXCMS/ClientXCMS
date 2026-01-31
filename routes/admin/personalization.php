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
use App\Http\Controllers\Admin\Personalization\EmailTemplateController;
use App\Http\Controllers\Admin\Personalization\MenuLinkController;
use App\Http\Controllers\Admin\Personalization\SectionController;
use App\Http\Controllers\Admin\Personalization\SettingsPersonalizationController;
use App\Http\Controllers\Admin\Personalization\SocialCrudController;
use App\Http\Controllers\Admin\Personalization\ThemeController;
use Illuminate\Support\Facades\Route;

if (!is_installed() || app()->runningInConsole()) {
    $types = ['front', 'bottom'];
} else {
    $types = \App\Models\Personalization\MenuLink::pluck('type')->unique()->toArray();
}

Route::name('personalization.')->prefix('/personalization')->group(function () {
    Route::resource('/socials', SocialCrudController::class)->names('socials')->except('edit');
    Route::post('/socials/sort', [SocialCrudController::class, 'sort'])->name('socials.sort');
    Route::put('/primary', [SettingsPersonalizationController::class, 'storePrimaryColors'])->name('primary');
    Route::post('/bottom_menu', [SettingsPersonalizationController::class, 'storeBottomMenu'])->name('bottom_menu');
    Route::post('/switch_theme/{theme}', [ThemeController::class, 'switchTheme'])->name('switch_theme');
    Route::post('/config_theme/{theme}', [ThemeController::class, 'configTheme'])->name('config_theme');
    Route::resource('sections', SectionController::class)->names('sections')->except('edit');
    Route::post('/sections/sort', [SectionController::class, 'sort'])->name('sections.sort')->withoutMiddleware('csrf');
    Route::post('/sections/{section}/clone', [SectionController::class, 'clone'])->name('sections.clone');
    Route::post('/sections/{section}/switch', [SectionController::class, 'switch'])->name('sections.switch');
    Route::post('/sections/{section}/restore', [SectionController::class, 'restore'])->name('sections.restore');
    Route::post('/sections/{section}/clone_section', [SectionController::class, 'cloneSection'])->name('sections.clone_section');
});
Route::resource('email_templates', EmailTemplateController::class)->names('personalization.email_templates');
Route::post('email_templates/import', [EmailTemplateController::class, 'import'])->name('personalization.email_templates.import');
Route::put('menulink/{menulink}', [MenuLinkController::class, 'update'])->name('personalization.menulinks.update');
Route::post('menulink/{type}', [MenuLinkController::class, 'store'])->whereIn('type', $types);
Route::get('menulink/{type}', [MenuLinkController::class, 'create'])->name('personalization.menulinks.create')->whereIn('type', $types);
Route::delete('menulink/{menulink}', [MenuLinkController::class, 'delete'])->name('personalization.menulinks.delete');
Route::get('menulink/{menulink}', [MenuLinkController::class, 'show'])->name('personalization.menulinks.show');
Route::post('menulink/{type}/sort', [MenuLinkController::class, 'sort'])->name('personalization.menulinks.sort')->withoutMiddleware('csrf');
Route::get('menulink/custom/{type}', [SettingsPersonalizationController::class, 'showCustomMenu'])->name('personalization.menulinks.custom');
Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::put('/personalization/seo', [SettingsPersonalizationController::class, 'storeSeoSettings'])->name('personalization.seo');
    Route::put('/personalization/home', [SettingsPersonalizationController::class, 'storeHomeSettings'])->name('personalization.home');
});
