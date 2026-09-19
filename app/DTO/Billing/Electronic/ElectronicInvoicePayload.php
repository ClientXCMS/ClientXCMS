<?php

namespace App\DTO\Billing\Electronic;

final readonly class ElectronicInvoicePayload
{
    public function __construct(
        public string $type,
        public string $number,
        public string $issuedAt,
        public string $currency,
        public array $seller,
        public array $buyer,
        public array $lines,
        public array $totals,
        public ?string $originalInvoiceNumber = null,
    ) {}
}
