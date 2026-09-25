<?php

namespace App\DTO\Billing\Electronic;

final readonly class ProviderStatusResult
{
    public function __construct(public string $status, public array $response = []) {}
}
