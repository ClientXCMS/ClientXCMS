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

use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\InvoiceController;
use App\Http\Controllers\Api\Client\PaymentMethodController;
use App\Http\Controllers\Api\Client\ProfileController;
use App\Http\Controllers\Api\Client\ServiceController;
use App\Http\Controllers\Api\Client\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the client application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group with Sanctum authentication.
|
*/

// Public authentication routes (no auth required)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/2fa/verify', [AuthController::class, 'verify2fa'])->name('auth.2fa.verify');

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'password'])->name('password');
        Route::get('/2fa/setup', [ProfileController::class, 'setup2fa'])->name('2fa.setup');
        Route::post('/2fa', [ProfileController::class, 'toggle2fa'])->name('2fa');
        Route::get('/2fa/recovery-codes', [ProfileController::class, 'recoveryCodes'])->name('2fa.recovery-codes');
        Route::post('/security-question', [ProfileController::class, 'saveSecurityQuestion'])->name('security-question');
        Route::delete('/', [ProfileController::class, 'deleteAccount'])->name('delete');
    });

    // Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/departments', [TicketController::class, 'departments'])->name('departments');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
        Route::post('/{ticket}/reopen', [TicketController::class, 'reopen'])->name('reopen');
        Route::get('/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('attachment');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
        Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
        Route::post('/{invoice}/pay/{gateway}', [InvoiceController::class, 'pay'])->name('pay');
        Route::post('/{invoice}/balance', [InvoiceController::class, 'balance'])->name('balance');
    });

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
    });

    // Payment Methods
    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::get('/gateways', [PaymentMethodController::class, 'gateways'])->name('gateways');
        Route::post('/{gateway}', [PaymentMethodController::class, 'add'])->name('add');
        Route::put('/{source}/default', [PaymentMethodController::class, 'setDefault'])->name('default');
        Route::delete('/{source}', [PaymentMethodController::class, 'delete'])->name('delete');
    });
});
