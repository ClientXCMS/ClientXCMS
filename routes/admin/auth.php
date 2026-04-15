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
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::get('/forgot-password', [PasswordResetLinkController::class, 'showForm'])->name('password.request')->withoutMiddleware('admin');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->withoutMiddleware('admin');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'showForm'])->name('password.reset')->withoutMiddleware('admin');
Route::get('/autologin/{id}/{token}', [AuthenticatedSessionController::class, 'autologin'])->whereNumber('id')->name('autologin')->withoutMiddleware('admin');
Route::get('/2fa', [TwoFactorAuthenticationController::class, 'show'])
    ->withoutMiddleware('auth')
    ->name('auth.2fa');
Route::post('/2fa', [TwoFactorAuthenticationController::class, 'verify'])
    ->middleware('throttle:6,1')
    ->withoutMiddleware('auth');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store')->withoutMiddleware('admin');
Route::get('/confirm-password', [AuthenticatedSessionController::class, 'confirmPassword'])->name('password.confirm')->middleware('admin');
Route::post('/confirm-password', [AuthenticatedSessionController::class, 'confirm'])->middleware('admin');
Route::get('/login', [LoginController::class, 'showForm'])->name('login')->withoutMiddleware('admin');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->withoutMiddleware('admin');
Route::any('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->withoutMiddleware('admin');
