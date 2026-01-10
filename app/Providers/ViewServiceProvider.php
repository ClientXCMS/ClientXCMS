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
        // Register addon view overrides from current theme
        $this->app->booted(function () {
            $theme = app('theme')->getTheme();
            if ($theme) {
                $themePath = $theme->path . '/views';

                // Scan for addon overrides in theme
                $addonOverridesPath = $themePath;
                if (is_dir($addonOverridesPath)) {
                    foreach (scandir($addonOverridesPath) as $dir) {
                        if ($dir === '.' || $dir === '..') continue;

                        $addonViewPath = $addonOverridesPath . '/' . $dir;
                        if (is_dir($addonViewPath) && str_contains($dir, '_')) {
                            // This looks like an addon namespace
                            $this->app['view']->prependNamespace($dir, $addonViewPath);
                        }
                    }
                }
            }
        });
    }
}
