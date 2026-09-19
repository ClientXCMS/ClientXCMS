<?php

namespace App\Contracts\Domain;

use App\DTO\Domain\DomainCatalogPage;
use App\Models\Provisioning\Server;

interface DomainCatalogInterface
{
    public function catalogPage(Server $server, int $offset = 0, int $limit = 100): DomainCatalogPage;
}
