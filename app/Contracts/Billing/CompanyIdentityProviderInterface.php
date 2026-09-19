<?php

namespace App\Contracts\Billing;

use App\DTO\Billing\CompanyIdentity;
use Illuminate\Support\Collection;

interface CompanyIdentityProviderInterface
{
    /** @return Collection<int, CompanyIdentity> */
    public function search(string $query, string $country = 'FR', int $page = 1): Collection;

    public function find(string $identifier, string $country = 'FR'): ?CompanyIdentity;
}
