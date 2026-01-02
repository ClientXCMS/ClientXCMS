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

namespace App\Extensions;

class BaseAddonServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Load database migrations
     */
    public function loadMigrations(): void
    {
        $this->loadMigrationsFrom($this->addonPath('database/migrations'));
    }

    protected function loadTranslations(): void
    {
        $langPath = $this->addonPath('lang');

        $this->loadTranslationsFrom($langPath, $this->uuid);
    }

    protected function loadViews(): void
    {
        $viewsPath = $this->addonPath('views');
        if (! is_dir($viewsPath)) {
            return;
        }
        $adminPath = $this->addonPath('views/admin');
        if (is_dir($adminPath)) {
            $this->loadViewsFrom($adminPath, $this->uuid.'_admin');
        }
        $hasTheme = app('theme')->hasTheme();
        if ($hasTheme) {
            $themePath = app('theme')->themepath().'views/'.$this->uuid;
            if (is_dir($themePath)) {
                $this->loadViewsFrom($themePath, $this->uuid);
            }
        }
        $defaultPath = $this->addonPath('views/default');
        if (is_dir($defaultPath)) {
            $this->loadViewsFrom($defaultPath, $this->uuid.($hasTheme ? '_default' : ''));
        }

        $bootstrapPath = $this->addonPath('views/bootstrap');
        if (is_dir($bootstrapPath)) {
            $this->loadViewsFrom($bootstrapPath, $this->uuid.'_bootstrap');
        }
    }

    public function addonPath(string $path = ''): string
    {
        return base_path('addons/'.$this->uuid.($path ? '/'.$path : $path));
    }
}
