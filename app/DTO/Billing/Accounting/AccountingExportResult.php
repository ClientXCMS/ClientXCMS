<?php

namespace App\DTO\Billing\Accounting;

final readonly class AccountingExportResult
{
    public function __construct(public string $status, public ?string $externalId = null, public array $response = []) {}
}
