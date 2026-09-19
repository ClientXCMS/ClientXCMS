<?php

namespace App\DTO\Billing\Electronic;

final readonly class ProviderSubmissionResult
{
    public function __construct(
        public string $externalId,
        public string $status,
        public array $response = [],
        public ?string $artifactPath = null,
    ) {}
}
