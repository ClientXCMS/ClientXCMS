<?php

namespace App\Services\Billing;

use App\Contracts\Billing\ElectronicExchangeProviderInterface;
use InvalidArgumentException;

class ElectronicProviderRegistry
{
    private array $providers = [];

    public function register(ElectronicExchangeProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(?string $key = null): ElectronicExchangeProviderInterface
    {
        $key ??= (string) setting('einvoicing_provider', 'local');

        return $this->providers[$key] ?? throw new InvalidArgumentException("Unknown electronic invoicing provider [{$key}].");
    }

    public function all(): array
    {
        return $this->providers;
    }
}
