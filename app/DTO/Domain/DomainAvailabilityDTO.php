<?php

namespace App\DTO\Domain;

class DomainAvailabilityDTO
{
    public function __construct(
        public string $domain,
        public bool $available,
        public ?string $message = null,
    ) {}
}
