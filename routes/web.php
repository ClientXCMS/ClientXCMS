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

use App\Http\Controllers\Admin\Security\LicenseController;
use App\Http\Controllers\DarkModeController;
use App\Http\Controllers\Front\Billing\PaymentGatewayController;
use App\Http\Controllers\GDPRController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|gateways
*/

Route::get('/', function () {
    if (! setting('theme_home_enabled')) {
        return redirect()->to(setting('theme_home_redirect_route', '/store'));
    }

    return view('home');
})->name('home');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\n";
    if (setting('seo_disablereferencement')) {
        $content .= "Disallow: /\n";
    } else {
        $content .= "Disallow:\n";
    }

    return response($content, 200, ['Content-Type' => 'text/plain']);
})->name('robots.txt');

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
Route::get('/licensing/return', [LicenseController::class, 'return'])->name('licensing.return');
Route::get('/locale/{locale}', [LocaleController::class, 'setLocale'])->name('locale');
Route::get('/darkmode', [DarkModeController::class, 'darkmode'])->name('darkmode.switch');
Route::get('/gdpr', [GDPRController::class, 'gdpr'])->name('gdpr');
Route::get('/gateways/{invoice:uuid}/{gateway}/return', [PaymentGatewayController::class, 'return'])->middleware(['auth'])->name('gateways.return');
Route::get('/gateways/{invoice:uuid}/{gateway}/cancel', [PaymentGatewayController::class, 'cancel'])->middleware(['auth'])->name('gateways.cancel');
Route::get('/source/gateway/{gateway}/return', [PaymentGatewayController::class, 'sourceReturn'])->middleware(['auth'])->name('gateways.source.return');
Route::any('/gateways/{gateway}/notification', [PaymentGatewayController::class, 'notification'])->withoutMiddleware('csrf')->name('gateways.notification');
Route::get('/docs/api-docs.json', [\App\Http\Controllers\ApiController::class, 'apiDocs'])->name('l5-swagger.application.docs');
Route::get('/docs/asset/{asset}', [\App\Http\Controllers\ApiController::class, 'apiAsset'])->name('l5-swagger.application.asset');
require __DIR__.'/client/invoices.php';
require __DIR__.'/client/helpdesk.php';
require __DIR__.'/client/services.php';
require __DIR__.'/client/client.php';
require __DIR__.'/store.php';
require __DIR__.'/auth.php';
