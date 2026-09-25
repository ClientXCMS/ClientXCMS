<?php

namespace App\DTO\Billing\Electronic;

readonly class ReportPayload
{
    public function __construct(
        public string $type,
        public string $periodStart,
        public string $periodEnd,
        public string $currency,
        public array $entries,
        public array $totals,
        public string $sha256,
    ) {}
}
