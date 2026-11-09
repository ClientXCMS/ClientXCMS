<?php

namespace App\DTO\Domain;

use App\Core\Domain\DomainTransferStatus;

class DomainTransferStatusDTO
{
    public function __construct(
        public DomainTransferStatus $status,
        public ?string $message = null,
    ) {}
}
