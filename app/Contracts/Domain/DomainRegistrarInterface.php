<?php

namespace App\Contracts\Domain;

use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Domain\DomainInfoDTO;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;

interface DomainRegistrarInterface
{
    public function uuid(): string;

    public function title(): string;

    public function validate(): array;

    public function testConnection(array $params): ConnectionResponse;

    public function checkAvailability(string $domain): DomainAvailabilityDTO;

    public function register(Service $service): ServiceStateChangeDTO;

    public function renew(Service $service, int $years): ServiceStateChangeDTO;

    public function getDomain(Service $service): DomainInfoDTO;

    public function getNameservers(Service $service): array;

    public function updateNameservers(Service $service, array $nameservers): ServiceStateChangeDTO;

    public function getDnsRecords(Service $service): array;

    public function createDnsRecord(Service $service, array $record): ServiceStateChangeDTO;

    public function updateDnsRecord(Service $service, string $recordId, array $record): ServiceStateChangeDTO;

    public function deleteDnsRecord(Service $service, string $recordId): ServiceStateChangeDTO;
}
