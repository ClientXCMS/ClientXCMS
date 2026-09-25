<?php

namespace App\DTO\Billing\Accounting;

final readonly class AccountingStatusResult
{
    public function __construct(public string $status, public array $response = []) {}
}
