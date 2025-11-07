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


namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/client';

    public const ADMIN_HOME = '/admin/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/customer')
            ->name('api.customer.')
            ->group(base_path('routes/api-customer.php'));
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/application')
            ->name('api.application.')
            ->group(base_path('routes/api-application.php'));
        Route::middleware(['web', 'admin'])
            ->prefix(admin_prefix())
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
        if (! is_installed()) {
            Route::middleware('web')
                ->prefix('install')
                ->name('install.')
                ->group(base_path('routes/install.php'));
        }
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
