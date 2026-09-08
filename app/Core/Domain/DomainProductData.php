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

        // Preserve the snapshot on an already configured basket row (including legacy rows).
        if (($productDataDTO->data['domain'] ?? null) === $domain && ! empty($productDataDTO->data['nameservers'])) {
            return array_merge($productDataDTO->data, ['domain' => $domain, 'tld' => $tld]);
        }
        $config = app(DomainPricingService::class)->findTld($tld);
        if (! $config || count($config->default_nameservers ?? []) < 2) {
            return ['error' => __('provisioning.admin.domain_tlds.tools.missing_nameservers')];
        }
        $server = $config->server ?? $productDataDTO->product->productType()->server()?->findServer($productDataDTO->product);

        return [
            'domain' => $domain, 'tld' => $tld, 'operation' => 'register',
            'provider' => $server?->hostname, 'domain_server_id' => $server?->id,
            'nameservers' => $config->default_nameservers,
            'default_dns_records' => $config->default_dns_records ?? [],
            'apply_default_dns' => (bool) $config->apply_default_dns,
            'dns_management' => (bool) $config->dns_management,
            'whois_privacy' => (bool) $config->whois_privacy,
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
