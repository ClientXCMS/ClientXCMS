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
use App\Http\Controllers\Admin\Core\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/customers/{customer}/send_password', [CustomerController::class, 'sendForgotPassword'])->name('customers.send_password');
Route::get('/customers/{customer}/resend_confirmation', [CustomerController::class, 'resendConfirmation'])->name('customers.resend_confirmation');
Route::get('/customers/{customer}/confirm', [CustomerController::class, 'confirm'])->name('customers.confirm');
Route::resource('/customers', CustomerController::class)->names('customers')->except('edit');
Route::get('/customers/{customer}/autologin', [CustomerController::class, 'autologin'])->name('customers.autologin');
Route::post('/customers/{customer}/action/{action}', [CustomerController::class, 'action'])->name('customers.action');
Route::post('/customers/{customer}/notes', [CustomerController::class, 'addNote'])->name('customers.notes.store');
Route::get('/auth/customers/logout', [CustomerController::class, 'logout'])->name('customers.logout');
Route::get('/search/customers', [CustomerController::class, 'customSearch'])->name('customers.search');
