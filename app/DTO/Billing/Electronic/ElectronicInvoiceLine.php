<?php

namespace App\DTO\Billing\Electronic;

final readonly class ElectronicInvoiceLine
{
    public function __construct(
        public string $identifier,
        public string $name,
        public string $quantity,
        public string $unitPriceExcludingTax,
        public string $vatRate,
        public string $taxCategory,
        public ?string $exemptionReason,
        public string $operationCategory,
    ) {}
}
