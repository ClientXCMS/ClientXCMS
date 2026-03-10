<?php

use App\Addons\HelpdeskBridge\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SettingsController::class, 'index'])->name('index');
Route::put('/', [SettingsController::class, 'update'])->name('update');
