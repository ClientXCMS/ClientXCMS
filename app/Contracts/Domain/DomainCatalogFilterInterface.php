<?php

namespace App\Contracts\Domain;

use App\DTO\Domain\DomainCatalogPage;
use App\Models\Provisioning\Server;

interface DomainCatalogFilterInterface
{
    /** @param array<int, string> $extensions */
    public function catalogExtensions(Server $server, array $extensions): DomainCatalogPage;
}
