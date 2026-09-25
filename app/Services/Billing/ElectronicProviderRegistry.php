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

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function supports(string $key, string $capability): bool
    {
        return $this->has($key) && in_array($capability, $this->providers[$key]->capabilities(), true);
    }

    public function all(): array
    {
        return $this->providers;
    }
}
