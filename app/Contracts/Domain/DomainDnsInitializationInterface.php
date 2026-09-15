<?php

namespace App\Contracts\Domain;

use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;

interface DomainDnsInitializationInterface
{
    /** @return array{nameservers:array,types:array} */
    public function dnsCapabilities(Server $server): array;

    /** Must return the complete zone, or throw. Never turn a failed read into an empty zone. */
    public function initializationRecords(Service $service): array;
}
