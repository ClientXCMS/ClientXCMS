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

use App\Http\Controllers\Api\Billing\InvoiceController;
use App\Http\Controllers\Api\Customers\CustomerController;
use App\Http\Controllers\Api\Helpdesk\DepartmentController;
use App\Http\Controllers\Api\Helpdesk\TicketController;
use App\Http\Controllers\Api\Provisioning\ServerController;
use App\Http\Controllers\Api\Provisioning\ServiceController;
use App\Http\Controllers\Api\Provisioning\SubdomainHostController;
use App\Http\Controllers\Api\Security\ActionLogController;
use App\Http\Controllers\Api\Store\CouponController;
use App\Http\Controllers\Api\Store\Groups\GroupController;
use App\Http\Controllers\Api\Store\Pricings\PricingController;
use App\Http\Controllers\Api\Store\Products\ProductController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['ability:health,*'])->get('/health', [ApiController::class, 'health'])->name('health');
Route::middleware(['ability:license,*'])->get('/license', [ApiController::class, 'license'])->name('license');

Route::middleware(['ability:customers:index,*'])->get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::middleware(['ability:customers:store,*'])->post('/customers', [CustomerController::class, 'store'])->name('customers.store');
Route::middleware(['ability:customers:show,*'])->get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
Route::middleware(['ability:customers:update,*'])->post('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::middleware(['ability:customers:delete,*'])->delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.delete');
Route::middleware(['ability:customers:update,*'])->post('/customers/{customer}/resend_confirmation', [CustomerController::class, 'resendConfirmation'])->name('customers.resend_confirmation');
Route::middleware(['ability:customers:update,*'])->post('/customers/{customer}/send_password', [CustomerController::class, 'sendForgotPassword'])->name('customers.send_password');
Route::middleware(['ability:customers:update,*'])->post('/customers/{customer}/confirm', [CustomerController::class, 'confirm'])->name('customers.confirm');
Route::middleware(['ability:customers:update,*'])->post('/customers/{customer}/action/{action}', [CustomerController::class, 'action'])->name('customers.action');

Route::middleware(['ability:products:index,*'])->get('/products', [ProductController::class, 'index'])->name('products.index');
Route::middleware(['ability:products:store,*'])->post('/products', [ProductController::class, 'store'])->name('products.store');
Route::middleware(['ability:products:show,*'])->get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::middleware(['ability:products:update,*'])->post('/products/{product}', [ProductController::class, 'update'])->name('products.update');
Route::middleware(['ability:products:delete,*'])->delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.delete');
Route::middleware(['ability:products:show,*'])->get('/products/{product}/config', [ProductController::class, 'config'])->name('products.config');
Route::middleware(['ability:products:update,*'])->put('/products/{product}/config', [ProductController::class, 'updateConfig'])->name('products.config.update');

Route::middleware(['ability:groups:index,*'])->get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::middleware(['ability:groups:store,*'])->post('/groups', [GroupController::class, 'store'])->name('groups.store');
Route::middleware(['ability:groups:show,*'])->get('/groups/{product}', [GroupController::class, 'show'])->name('groups.show');
Route::middleware(['ability:groups:update,*'])->post('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
Route::middleware(['ability:groups:delete,*'])->delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.delete');

Route::middleware(['ability:pricings:index,*'])->get('/pricings', [PricingController::class, 'index'])->name('pricings.index');
Route::middleware(['ability:pricings:store,*'])->post('/pricings', [PricingController::class, 'store'])->name('pricings.store');
Route::middleware(['ability:pricings:show,*'])->get('/pricings/{pricing}', [PricingController::class, 'show'])->name('pricings.show');
Route::middleware(['ability:pricings:update,*'])->post('/pricings/{pricing}', [PricingController::class, 'update'])->name('pricings.update');
Route::middleware(['ability:pricings:delete,*'])->delete('/pricings/{pricing}', [PricingController::class, 'destroy'])->name('pricings.delete');

Route::middleware(['ability:services:index,*'])->get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::middleware(['ability:services:show,*'])->get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::middleware(['ability:services:delete,*'])->delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.delete');
Route::middleware(['ability:services:expire,*'])->post('/services/{service}/expire', [ServiceController::class, 'expire'])->name('services.expire');
Route::middleware(['ability:services:unsuspend,*'])->post('/services/{service}/unsuspend', [ServiceController::class, 'unsuspend'])->name('services.unsuspend');
Route::middleware(['ability:services:suspend,*'])->post('/services/{service}/suspend', [ServiceController::class, 'suspend'])->name('services.suspend');

Route::middleware(['ability:invoices:index,*'])->get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::middleware(['ability:invoices:store,*'])->post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
Route::middleware(['ability:invoices:show,*'])->get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::middleware(['ability:invoices:update,*'])->post('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
Route::middleware(['ability:invoices:delete,*'])->delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.delete');
Route::middleware(['ability:invoices:update,*'])->get('/invoices/{invoice}/notify', [InvoiceController::class, 'notify'])->name('invoices.notify');
Route::middleware(['ability:invoices:show,*'])->get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::middleware(['ability:invoices:show,*'])->get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
Route::middleware(['ability:invoices:index,*'])->post('/invoices/{invoice}/export', [InvoiceController::class, 'excel'])->name('invoices.export');
Route::middleware(['ability:invoices:update,*'])->post('/invoices/{invoice}/validate', [InvoiceController::class, 'validate'])->name('invoices.validate');
Route::middleware(['ability:invoices:update,*'])->post('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');

// Departments
Route::middleware(['ability:departments:index,*'])->get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::middleware(['ability:departments:store,*'])->post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
Route::middleware(['ability:departments:show,*'])->get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
Route::middleware(['ability:departments:update,*'])->post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
Route::middleware(['ability:departments:delete,*'])->delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.delete');

// Tickets
Route::middleware(['ability:tickets:index,*'])->get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::middleware(['ability:tickets:store,*'])->post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::middleware(['ability:tickets:show,*'])->get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::middleware(['ability:tickets:update,*'])->post('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
Route::middleware(['ability:tickets:delete,*'])->delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.delete');
Route::middleware(['ability:tickets:reply,*'])->post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
Route::middleware(['ability:tickets:close,*'])->post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
Route::middleware(['ability:tickets:reopen,*'])->post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');

// Coupons
Route::middleware(['ability:coupons:index,*'])->get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
Route::middleware(['ability:coupons:store,*'])->post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
Route::middleware(['ability:coupons:show,*'])->get('/coupons/{coupon}', [CouponController::class, 'show'])->name('coupons.show');
Route::middleware(['ability:coupons:update,*'])->post('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
Route::middleware(['ability:coupons:delete,*'])->delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.delete');

// Servers
Route::middleware(['ability:servers:index,*'])->get('/servers', [ServerController::class, 'index'])->name('servers.index');
Route::middleware(['ability:servers:store,*'])->post('/servers', [ServerController::class, 'store'])->name('servers.store');
Route::middleware(['ability:servers:show,*'])->get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
Route::middleware(['ability:servers:update,*'])->post('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
Route::middleware(['ability:servers:delete,*'])->delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.delete');
Route::middleware(['ability:servers:test,*'])->post('/servers/test', [ServerController::class, 'test'])->name('servers.test');

// Action Logs (read-only)
Route::middleware(['ability:logs:index,*'])->get('/logs', [ActionLogController::class, 'index'])->name('logs.index');
Route::middleware(['ability:logs:show,*'])->get('/logs/{log}', [ActionLogController::class, 'show'])->name('logs.show');

// Subdomain Hosts
Route::middleware(['ability:subdomains:index,*'])->get('/subdomains', [SubdomainHostController::class, 'index'])->name('subdomains.index');
Route::middleware(['ability:subdomains:store,*'])->post('/subdomains', [SubdomainHostController::class, 'store'])->name('subdomains.store');
Route::middleware(['ability:subdomains:show,*'])->get('/subdomains/{subdomain}', [SubdomainHostController::class, 'show'])->name('subdomains.show');
Route::middleware(['ability:subdomains:update,*'])->post('/subdomains/{subdomain}', [SubdomainHostController::class, 'update'])->name('subdomains.update');
Route::middleware(['ability:subdomains:delete,*'])->delete('/subdomains/{subdomain}', [SubdomainHostController::class, 'destroy'])->name('subdomains.delete');

// Cancellation Reasons
Route::middleware(['ability:cancellation_reasons:index,*'])->get('/cancellation_reasons', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'index'])->name('cancellation_reasons.index');
Route::middleware(['ability:cancellation_reasons:store,*'])->post('/cancellation_reasons', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'store'])->name('cancellation_reasons.store');
Route::middleware(['ability:cancellation_reasons:show,*'])->get('/cancellation_reasons/{cancellation_reason}', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'show'])->name('cancellation_reasons.show');
Route::middleware(['ability:cancellation_reasons:update,*'])->post('/cancellation_reasons/{cancellation_reason}', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'update'])->name('cancellation_reasons.update');
Route::middleware(['ability:cancellation_reasons:delete,*'])->delete('/cancellation_reasons/{cancellation_reason}', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'destroy'])->name('cancellation_reasons.delete');
Route::middleware(['ability:cancellation_reasons:analytics,*'])->get('/cancellation_reasons_analytics', [\App\Http\Controllers\Api\Provisioning\CancellationReasonController::class, 'analytics'])->name('cancellation_reasons.analytics');
