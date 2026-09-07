<?php

namespace App\DTO\Billing;

final readonly class CompanyIdentity
{
    public function __construct(
        public string $provider,
        public string $country,
        public string $legalName,
        public ?string $siren = null,
        public ?string $siret = null,
        public ?string $vatNumber = null,
        public ?string $address = null,
        public ?string $zipcode = null,
        public ?string $city = null,
        public ?string $administrativeStatus = null,
        public ?string $activityCode = null,
        public ?string $legalCategory = null,
        public bool $association = false,
        public ?string $rnaNumber = null,
    ) {}

    public function isAssociation(): bool
    {
        return $this->association || str_starts_with((string) $this->legalCategory, '92');
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
