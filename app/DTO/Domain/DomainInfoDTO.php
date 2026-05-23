<?php

namespace App\DTO\Domain;

use Carbon\Carbon;

class DomainInfoDTO
{
    public function __construct(
        public string $domain,
        public string $status,
        public ?Carbon $createdAt = null,
        public ?Carbon $expiresAt = null,
        public ?array $nameservers = null,
        public ?string $registrarId = null,
    ) {}
}
