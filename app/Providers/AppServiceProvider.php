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

use App\Core\Auth\MigratingHashManager;
use App\Core\License\LicenseGateway;
use App\Services\Core\SeoService;
use App\View\Components\BadgeStateComponant;
use App\View\Components\Provisioning\ServiceDaysRemaining;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\NotPwnedVerifier;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    const VERSION = '2.17';

    // Laravel waits 30s by default, too long to keep a customer staring at a form
    const BREACH_CHECK_TIMEOUT = 5;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('license', LicenseGateway::class);
        $this->app->singleton('seo', SeoService::class);
        // extend, not singleton: HashServiceProvider is deferred and would overwrite the binding
        $this->app->extend('hash', fn ($manager, $app) => new MigratingHashManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Europe/Paris');
        $this->definePasswordRules();

        if ($this->app->environment('production') && config('app.debug')) {
            // si il n'y a pas d'admin connecté, on désactive le debug pour éviter de divulguer des informations sensibles
            if (! auth()->check() || ! auth('admin')->check() || ! auth('admin')->user()) {
                config(['app.debug' => false]);
                logger()->warning('APP_DEBUG was true in production environment - forced to false at boot. Operator must set APP_DEBUG=false explicitly in .env to silence this.');
            }
        }

        if (env('APP_FORCE_HTTPS', $this->app->environment('production'))) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Builder::macro('whereLike', function (string $attribute, string $searchTerm) {
            return $this->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
        });
        Paginator::defaultView('shared.pagination.default');
        Blade::component('badge-state', BadgeStateComponant::class);
        Blade::component('service-days-remaining', ServiceDaysRemaining::class);
        \View::share('clientxcms_version', self::VERSION);
        Carbon::setLocale(setting('app.locale', 'fr_FR'));
    }

    // No composition rule on purpose: OWASP advises against it, length and breach checks are what count
    private function definePasswordRules(): void
    {
        // bcrypt silently truncates past 72 bytes, so anything beyond does not count
        Password::defaults(function () {
            $rule = Password::min(12)->max(72);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // extend, not singleton: ValidationServiceProvider is deferred and would overwrite it
        $this->app->extend(UncompromisedVerifier::class, function ($verifier, $app) {
            return new NotPwnedVerifier($app[HttpFactory::class], self::BREACH_CHECK_TIMEOUT);
        });
    }
}
