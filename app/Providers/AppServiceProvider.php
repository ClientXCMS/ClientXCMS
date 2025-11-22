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

use App\Core\License\LicenseGateway;
use App\Services\Core\SeoService;
use App\View\Components\BadgeStateComponant;
use App\View\Components\Provisioning\ServiceDaysRemaining;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    const VERSION = '2.14.9';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('license', LicenseGateway::class);
        $this->app->singleton('seo', SeoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Europe/Paris');

        Builder::macro('whereLike', function (string $attribute, string $searchTerm) {
            return $this->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
        });
        Paginator::defaultView('shared.pagination.default');
        Blade::component('badge-state', BadgeStateComponant::class);
        Blade::component('service-days-remaining', ServiceDaysRemaining::class);
        \View::share('clientxcms_version', self::VERSION);
        Carbon::setLocale(setting('app.locale', 'fr_FR'));
    }
}
