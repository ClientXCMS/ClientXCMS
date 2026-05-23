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
use App\Http\Controllers\Admin\Core\CustomerSubUserController;
use Illuminate\Support\Facades\Route;

Route::get('/customers/{customer}/send_password', [CustomerController::class, 'sendForgotPassword'])->name('customers.send_password');
Route::post('/customers/{customer}/resend_confirmation', [CustomerController::class, 'resendConfirmation'])->name('customers.resend_confirmation');
Route::post('/customers/{customer}/confirm', [CustomerController::class, 'confirm'])->name('customers.confirm');
Route::resource('/customers', CustomerController::class)->names('customers')->except('edit');
// v2.16 — bulk endpoint declared before the {customer} routes so it isn't
// interpreted as a resource id by Laravel's route compiler.
Route::post('/customers/bulk', [CustomerController::class, 'bulk'])->name('customers.bulk');
Route::post('/customers/{customer}/autologin', [CustomerController::class, 'autologin'])->name('customers.autologin');
Route::post('/customers/{customer}/action/{action}', [CustomerController::class, 'action'])->name('customers.action');
Route::post('/customers/{customer}/notes', [CustomerController::class, 'addNote'])->name('customers.notes.store');
Route::put('/customers/{customer}/subusers/{access}', [CustomerSubUserController::class, 'update'])->name('customers.subusers.update');
Route::delete('/customers/{customer}/subusers/{access}', [CustomerSubUserController::class, 'destroy'])->name('customers.subusers.destroy');
Route::delete('/customers/{customer}/subusers/invitations/{invitation}', [CustomerSubUserController::class, 'revokeInvitation'])->name('customers.subusers.invitations.revoke');
Route::post('/auth/customers/logout', [CustomerController::class, 'logout'])->name('customers.logout');
Route::get('/search/customers', [CustomerController::class, 'customSearch'])->name('customers.search');
