<?php

namespace App\Contracts\Billing;

use App\DTO\Billing\VatValidationResult;

interface VatNumberValidatorInterface
{
    public function validate(string $vatNumber, ?string $country = null, ?array $requester = null): VatValidationResult;

    public function status(): array;
}
