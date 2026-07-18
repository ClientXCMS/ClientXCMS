<?php

namespace App\Services\Domain;

use App\Contracts\Domain\DomainRegistrarInterface;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use Illuminate\Support\Collection;

class DomainRegistrarManager
{
    private Collection $registrars;

    public function __construct()
    {
        $this->registrars = collect();
    }

    public function register(DomainRegistrarInterface $registrar): void
    {
        $this->registrars->put($registrar->uuid(), $registrar);
    }

    public function all(): Collection
    {
        return $this->registrars;
    }

    public function get(?string $uuid): ?DomainRegistrarInterface
    {
        return $this->registrars->get($uuid ?: 'fake') ?? $this->registrars->first();
    }

    public function fromServer(?Server $server): ?DomainRegistrarInterface
    {
        if ($server === null) {
            return $this->get('fake');
        }

        return $this->get($server->hostname ?: $server->address);
    }

    public function fromService(Service $service): ?DomainRegistrarInterface
    {
        return $this->fromServer($service->server) ?? $this->get($service->data['provider'] ?? null);
    }
}
