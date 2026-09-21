<?php

use App\Addons\ChorusPro\Controllers\ChorusProSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin'])->prefix(admin_prefix().'/settings/billing/chorus-pro')->group(function () {
    Route::get('/', [ChorusProSettingsController::class, 'edit'])->name('admin.chorus-pro.settings');
    Route::put('/', [ChorusProSettingsController::class, 'update'])->name('admin.chorus-pro.settings.update');
    Route::post('/test', [ChorusProSettingsController::class, 'test'])->name('admin.chorus-pro.settings.test');
});
