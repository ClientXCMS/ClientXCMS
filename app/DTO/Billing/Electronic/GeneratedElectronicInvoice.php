<?php

namespace App\DTO\Billing\Electronic;

final readonly class GeneratedElectronicInvoice
{
    public function __construct(
        public string $profile,
        public string $xml,
        public string $pdf,
        public string $sha256,
        public array $manifest = [],
    ) {}
}
