<?php

namespace App\Addons\ChorusPro;

use App\Extensions\BaseAddonServiceProvider;
use App\Services\Billing\ElectronicProviderRegistry;

class ChorusProServiceProvider extends BaseAddonServiceProvider
{
    protected string $uuid = 'chorus-pro';

    public function register(): void
    {
        $this->app->singleton(ChorusProExchangeProvider::class);
        $this->app->afterResolving(ElectronicProviderRegistry::class, fn (ElectronicProviderRegistry $registry) => $registry->register($this->app->make(ChorusProExchangeProvider::class)));
    }

    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');

        if (! app()->runningInConsole() && ! app()->runningUnitTests()) {
            $this->app['settings']->addCardItem('billing', 'chorus_pro', 'chorus-pro::messages.settings.title', 'chorus-pro::messages.settings.description', 'bi bi-building', route('admin.chorus-pro.settings'), 'admin.manage_settings');
        }
    }
}
