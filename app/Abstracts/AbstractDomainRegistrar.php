<?php

namespace App\Abstracts;

use App\Contracts\Domain\DomainRegistrarInterface;
use App\DTO\Domain\DomainInfoDTO;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;
use BadMethodCallException;

/** Optional operations fail explicitly; essential operations remain abstract via the interface. */
abstract class AbstractDomainRegistrar implements DomainRegistrarInterface
{
    public function checkAvailabilityBatch(array $domains): array
    {
        $results = [];
        foreach ($domains as $domain) {
            $results[$domain] = $this->checkAvailability($domain);
        }

        return $results;
    }

    public function supportsTransfer(): bool
    {
        return false;
    }

    public function transfer(Service $service): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }
    public function validate(): array
    {
        return [];
    }

    public function getDomain(Service $service): DomainInfoDTO
    {
        throw new BadMethodCallException('Domain information is not supported by this registrar');
    }

    public function getNameservers(Service $service): array
    {
        throw new BadMethodCallException('Nameservers are not supported by this registrar');
    }

    public function getDnsRecords(Service $service): array
    {
        throw new BadMethodCallException('DNS records are not supported by this registrar');
    }

    public function updateNameservers(Service $service, array $nameservers): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }

    public function createDnsRecord(Service $service, array $record): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }

    public function updateDnsRecord(Service $service, string $recordId, array $record): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }

    public function deleteDnsRecord(Service $service, string $recordId): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }

    protected function unsupported(Service $service): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, false, 'Operation is not supported by this registrar');
    }
}
