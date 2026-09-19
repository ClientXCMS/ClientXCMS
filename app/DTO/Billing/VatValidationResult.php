<?php

namespace App\DTO\Billing;

final readonly class VatValidationResult
{
    public function __construct(
        public string $provider,
        public string $status,
        public string $countryCode,
        public string $vatNumber,
        public ?string $requestIdentifier = null,
        public ?string $requestDate = null,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $nameMatch = null,
        public ?string $addressMatch = null,
        public ?string $error = null,
        public array $raw = [],
    ) {}

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function toArray(bool $includeRaw = false): array
    {
        $data = get_object_vars($this);
        if (! $includeRaw) {
            unset($data['raw']);
        }

        return $data;
    }
}
