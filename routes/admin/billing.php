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
use App\Http\Controllers\Admin\Billing\InvoiceController;
use App\Http\Controllers\Admin\Billing\SubscriptionController;
use App\Http\Controllers\Admin\Core\DashboardController;
use App\Http\Controllers\Admin\Settings\SettingsBillingController;
use Illuminate\Support\Facades\Route;

Route::get('/earn', [DashboardController::class, 'earn'])->name('earn')->middleware('password.confirm:admin.password.confirm');
Route::resource('/invoices', InvoiceController::class)->names('invoices')->except('edit');
Route::get('/invoices/{invoice}/notify', [InvoiceController::class, 'notify'])->name('invoices.notify');
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::post('/invoices/{invoice}/draft', [InvoiceController::class, 'draft'])->name('invoices.draft');
Route::post('/invoices/{invoice}/validate', [InvoiceController::class, 'validateInvoice'])->name('invoices.validate');
Route::post('/invoices/{invoice}/edit', [InvoiceController::class, 'editInvoice'])->name('invoices.edit');
Route::get('/invoices/{invoice}/config', [InvoiceController::class, 'config'])->name('invoices.config');
Route::post('/invoices/{invoice}/deliver/{invoiceItem}', [InvoiceController::class, 'deliver'])->name('invoices.deliver');
Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'payInvoice'])->name('invoices.pay');
Route::delete('invoices/{invoice}/delete/{invoiceItem}', [InvoiceController::class, 'deleteItem'])->name('invoices.deleteitem');
Route::patch('invoices/{invoice}/update/{invoiceItem}', [InvoiceController::class, 'updateItem'])->name('invoices.updateitem');
Route::post('invoices/{invoice}/cancel/{invoiceItem}', [InvoiceController::class, 'cancelItem'])->name('invoices.cancelitem');
Route::post('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
Route::post('/invoices/mass_action', [InvoiceController::class, 'massAction'])->name('invoices.mass_action');
Route::resource('/subscriptions', SubscriptionController::class)->names('subscriptions')->except('edit');

Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::put('/billing/billing', [SettingsBillingController::class, 'saveBilling'])->name('store.billing.save');
});
