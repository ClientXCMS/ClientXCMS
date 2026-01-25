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
            $this->registerThemeViewOverrides();
        });
    }

    /**
     * Register theme view overrides for admin and addon views.
     *
     * This allows themes to override:
     * 1. Admin views by placing files in {theme}/views/admin/
     * 2. Addon views by placing files in {theme}/views/{addon_namespace}/
     *
     * Addon namespaces are identified by underscore in directory name (e.g., quote_manager).
     */
    private function registerThemeViewOverrides(): void
    {
        $theme = app('theme')->getTheme();
        if (!$theme) {
            return;
        }

        $themePath = $theme->path . '/views';
        if (!is_dir($themePath)) {
            return;
        }

        $this->registerAdminViewOverrides($themePath);
        $this->registerAddonViewOverrides($themePath);
    }

    /**
     * Allow theme to override admin views by prepending theme path.
     */
    private function registerAdminViewOverrides(string $themePath): void
    {
        $adminPath = $themePath . '/admin';
        if (!is_dir($adminPath)) {
            return;
        }

        $finder = $this->app['view']->getFinder();
        $currentPaths = $finder->getPaths();
        $resourceViewsIndex = array_search(resource_path('views'), $currentPaths);

        if ($resourceViewsIndex !== false) {
            array_splice($currentPaths, $resourceViewsIndex, 0, [$themePath]);
            $finder->setPaths(array_unique($currentPaths));
        }
    }

    /**
     * Allow theme to override addon views using namespaced directories.
     *
     * Directories containing underscores are treated as addon namespaces.
     * Example: {theme}/views/quote_manager/ overrides quote_manager::* views.
     */
    private function registerAddonViewOverrides(string $themePath): void
    {
        $directories = scandir($themePath);
        if ($directories === false) {
            return;
        }

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $addonViewPath = $themePath . '/' . $dir;
            if (is_dir($addonViewPath) && str_contains($dir, '_')) {
                $this->app['view']->prependNamespace($dir, $addonViewPath);
            }
        }
    }
}
