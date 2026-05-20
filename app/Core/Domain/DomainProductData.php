<?php

namespace App\Core\Domain;

use App\Abstracts\AbstractProductData;
use App\DTO\Store\ProductDataDTO;
use App\Services\Domain\DomainPricingService;
use Illuminate\Validation\Rule;

class DomainProductData extends AbstractProductData
{
    public function primary(ProductDataDTO $productDataDTO): string
    {
        return $productDataDTO->data['domain'] ?? '';
    }

    public function validate(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9][a-z0-9-]*(\.[a-z0-9][a-z0-9-]*)+$/i'],
            'tld' => ['required', 'string', Rule::exists('domain_tlds', 'extension')->where('status', 'active')],
        ];
    }

    public function parameters(ProductDataDTO $productDataDTO): array
    {
        $domain = strtolower(trim($productDataDTO->parameters['domain'] ?? $productDataDTO->data['domain'] ?? ''));
        $tld = DomainPricingService::normalizeExtension($productDataDTO->parameters['tld'] ?? $productDataDTO->data['tld'] ?? '');
        if ($domain !== '' && ! str_ends_with($domain, $tld)) {
            return ['error' => __('provisioning.domain_manager.errors.invalid_tld')];
        }

        return [
            'domain' => $domain,
            'tld' => $tld,
            'operation' => 'register',
            'provider' => 'fake',
            'nameservers' => ['ns1.example.net', 'ns2.example.net'],
        ];
    }

    public function render(ProductDataDTO $productDataDTO)
    {
        return view('front.store.basket.domain', [
            'data' => $productDataDTO->data,
            'tlds' => \App\Models\Store\DomainTld::where('status', 'active')->orderBy('extension')->pluck('extension', 'extension'),
        ]);
    }

    public function renderAdmin(ProductDataDTO $productDataDTO)
    {
        return $this->render($productDataDTO);
    }
}
