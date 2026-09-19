<?php

namespace App\Services\Billing;

use App\Contracts\Billing\AccountingExportProviderInterface;

class AccountingProviderRegistry
{
    private array $providers = [];

    public function register(AccountingExportProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): AccountingExportProviderInterface
    {
        return $this->providers[$key] ?? throw new \InvalidArgumentException("Unknown accounting provider [{$key}].");
    }

    public function enabled(): array
    {
        return array_filter($this->providers, fn ($provider) => $provider->enabled());
    }

    public function all(): array
    {
        return $this->providers;
    }
}
