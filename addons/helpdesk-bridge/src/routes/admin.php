<?php

use App\Addons\HelpdeskBridge\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
