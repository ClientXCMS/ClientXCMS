<?php

namespace App\Contracts\Domain;

use App\DTO\Domain\DomainTransferStatusDTO;
use App\Models\Provisioning\Service;

interface DomainTransferStatusInterface
{
    /**
     * Reports an incoming transfer submitted by transfer(). Must not change the service data:
     * DomainTransferMonitor owns the transition. Throws when the registrar cannot answer.
     */
    public function transferStatus(Service $service): DomainTransferStatusDTO;
}
