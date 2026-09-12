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
            'nameserver_mode' => ['nullable', Rule::in(['managed', 'custom'])],
            'nameservers' => ['exclude_unless:nameserver_mode,custom', 'required', 'array', 'min:2', 'max:8'],
            'nameservers.*' => ['exclude_unless:nameserver_mode,custom', 'required', 'string', 'distinct', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i'],
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
        if (($productDataDTO->data['domain'] ?? null) === $domain && ! empty($productDataDTO->data['nameservers']) && ! array_key_exists('nameserver_mode', $productDataDTO->parameters)) {
            return array_merge($productDataDTO->data, ['domain' => $domain, 'tld' => $tld]);
        }
        $config = app(DomainPricingService::class)->findTld($tld);
        if (! $config || count($config->default_nameservers ?? []) < 2) {
            return ['error' => __('provisioning.admin.domain_tlds.tools.missing_nameservers')];
        }
        $server = $config->server ?? $productDataDTO->product->productType()->server()?->findServer($productDataDTO->product);

        $nameserverMode = $productDataDTO->parameters['nameserver_mode'] ?? 'managed';
        $nameservers = $nameserverMode === 'custom'
            ? array_values(array_filter(array_map(fn ($name) => strtolower(rtrim(trim((string) $name), '.')), $productDataDTO->parameters['nameservers'] ?? [])))
            : $config->default_nameservers;

        return [
            'domain' => $domain, 'tld' => $tld, 'operation' => 'register',
            'provider' => $server?->hostname, 'domain_server_id' => $server?->id,
            'nameserver_mode' => $nameserverMode,
            'nameservers' => $nameservers,
            'default_dns_records' => $config->default_dns_records ?? [],
            'apply_default_dns' => (bool) $config->apply_default_dns,
            'dns_management' => (bool) $config->dns_management,
            'whois_privacy' => (bool) $config->whois_privacy,
        ];
    }

    public function render(ProductDataDTO $productDataDTO)
    {
        $tlds = \App\Models\Store\DomainTld::where('status', 'active')->orderBy('extension')->get();

        return view('front.store.basket.domain', [
            'data' => $productDataDTO->data,
            'tlds' => $tlds->pluck('extension', 'extension'),
            'tldNameservers' => $tlds->mapWithKeys(fn ($tld) => [
                $tld->extension => collect($tld->default_nameservers ?? [])->map(fn ($hostname, $index) => [
                    'hostname' => $hostname,
                    'ipv4' => $tld->default_nameserver_ips[$index]['ipv4'] ?? null,
                    'ipv6' => $tld->default_nameserver_ips[$index]['ipv6'] ?? null,
                ])->values(),
            ]),
        ]);
    }

    public function renderAdmin(ProductDataDTO $productDataDTO)
    {
        return $this->render($productDataDTO);
    }
}
