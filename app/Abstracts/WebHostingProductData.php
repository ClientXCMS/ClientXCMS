<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Abstracts;

use App\DTO\Store\ProductDataDTO;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\SubdomainHost;
use App\Models\Store\Product;
use App\Rules\DomainIsNotRegisted;
use App\Rules\FQDN;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class WebHostingProductData extends AbstractProductData
{
    protected array $parameters = [
        'domain',
        'domain_subdomain',
        'subdomain',
    ];

    protected string $view = 'front.store.basket.webhosting';

    public function primary(\App\DTO\Store\ProductDataDTO $productDataDTO): string
    {
        return $productDataDTO->data['domain'] ?? '';
    }

    public function validate(): array
    {
        $subdomains = $this->availableSubdomainsForCurrentProduct();

        return [
            'domain' => ['nullable', 'max:255', new FQDN, new DomainIsNotRegisted, new RequiredIf(function () {
                return request()->input('domain_subdomain') == null;
            }),
            ],

            'domain_subdomain' => ['nullable', 'string', 'max:255', new DomainIsNotRegisted(true),
                new RequiredIf(function () use ($subdomains) {
                    return request()->input('domain') == null && $subdomains->count() > 0;
                }),
                'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/',
            ],
            'subdomain' => ['nullable', 'string', 'max:255', Rule::in($subdomains->pluck('domain')->toArray()),
                new RequiredIf(function () use ($subdomains) {
                    return request()->input('domain') == null && $subdomains->count() > 0;
                }),
            ],

        ];
    }

    public function parameters(ProductDataDTO $productDataDTO): array
    {
        $parameters = parent::parameters($productDataDTO);
        if (request()->input('domain') == null) {
            $parameters['domain'] = request()->input('domain_subdomain').request()->input('subdomain');
        } else {
            $parameters['domain'] = request()->input('domain');
        }
        $parameters['domain'] = strtolower($parameters['domain']);

        return $parameters;
    }

    public function renderAdmin(ProductDataDTO $productDataDTO)
    {
        $this->view = 'admin.store.webhosting';

        return $this->render($productDataDTO);
    }

    public function render(ProductDataDTO $productDataDTO)
    {
        $subdomains = SubdomainHost::availableForProduct($productDataDTO->product)->get();

        return view($this->view, [
            'productData' => $productDataDTO,
            'data' => $productDataDTO->data,
            'subdomains' => $subdomains,
        ])->render();
    }

    private function availableSubdomainsForCurrentProduct(): Collection
    {
        return SubdomainHost::availableForProduct($this->currentProduct())->get();
    }

    private function currentProduct(): ?Product
    {
        $product = request()->route('product');
        if ($product instanceof Product) {
            return $product;
        }

        $invoiceItem = request()->route('invoiceItem');
        if ($invoiceItem instanceof InvoiceItem && $invoiceItem->relatedType() instanceof Product) {
            return $invoiceItem->relatedType();
        }

        if (request()->input('related') === 'product' && request()->input('related_id') != null) {
            return Product::find(request()->input('related_id'));
        }

        $productId = request()->input('product_id');
        if ($productId == null) {
            return null;
        }

        return Product::find($productId);
    }
}
