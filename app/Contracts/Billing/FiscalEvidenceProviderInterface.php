<?php

namespace App\Contracts\Billing;

use App\Models\Account\Customer;

interface FiscalEvidenceProviderInterface
{
    public function evidenceFor(Customer $customer): ?array;

    public function latestEvidenceFor(Customer $customer): ?array;
}
