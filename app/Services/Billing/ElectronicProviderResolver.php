<?php

namespace App\Services\Billing;

use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;

class ElectronicProviderResolver
{
    public function __construct(private ElectronicProviderRegistry $registry) {}

    public function forDocument(Invoice|CreditNote $document): ?string
    {
        $invoice = $document instanceof CreditNote ? $document->invoice : $document;
        $snapshot = $invoice->billing_snapshot ?? [];
        $isPublic = filter_var(data_get($snapshot, 'buyer.is_public_entity', false), FILTER_VALIDATE_BOOL);

        return $this->resolve(
            (string) setting('einvoicing_provider', 'local'),
            (string) setting('einvoicing_b2g_provider', 'chorus-pro'),
            $isPublic,
        );
    }

    public function resolve(string $mainProvider, string $b2gProvider, bool $isPublic): ?string
    {
        $provider = $isPublic ? $b2gProvider : $mainProvider;

        if (! $this->registry->has($provider)) {
            return null;
        }

        $requiredCapability = $isPublic ? 'b2g' : 'b2b';

        return $this->registry->supports($provider, $requiredCapability) ? $provider : null;
    }

    public function forReporting(): ?string
    {
        $provider = (string) setting('einvoicing_provider', 'local');

        return $this->registry->has($provider)
            && ($this->registry->supports($provider, 'transaction_reporting') || $this->registry->supports($provider, 'payment_reporting'))
                ? $provider
                : null;
    }
}
