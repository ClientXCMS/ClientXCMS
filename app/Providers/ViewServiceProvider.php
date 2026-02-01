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

use App\View\ThemeViewFinder;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('view.finder', function ($app) {
            return new ThemeViewFinder($app['files'], $app['config']['view.paths']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $this->registerThemeNamespaceOverrides();
        });
    }

    /**
     * Prepend theme paths into every registered view namespace
     * so that themes can override addon views.
     */
    private function registerThemeNamespaceOverrides(): void
    {
        $themeManager = app('theme');
        if ($themeManager->getTheme() === null) {
            return;
        }

        $themePath = $themeManager->themePath('views');
        if ($themePath === null || ! is_dir($themePath)) {
            return;
        }

        $finder = app('view')->getFinder();
        $namespaces = $finder->getHints();

        foreach ($namespaces as $namespace => $paths) {
            $overridePath = $themePath.'/'.$namespace;
            if (is_dir($overridePath)) {
                $finder->prependNamespace($namespace, $overridePath);
            }
        }

        $finder->flush();
    }
}
