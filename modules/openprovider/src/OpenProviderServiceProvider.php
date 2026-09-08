<?php

namespace App\Modules\OpenProvider;

use App\Extensions\BaseModuleServiceProvider;
use App\Services\Domain\DomainRegistrarManager;

class OpenProviderServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Openprovider';

    protected string $version = '1.0.0';

    protected string $uuid = 'openprovider';

    public function register(): void
    {
        $this->app->afterResolving(DomainRegistrarManager::class, function (DomainRegistrarManager $manager) {
            $manager->register(new OpenProviderDomainRegistrar);
        });
    }

    public function boot(): void
    {
        app(DomainRegistrarManager::class)->register(new OpenProviderDomainRegistrar);
    }
}
