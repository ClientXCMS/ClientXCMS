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
 * Year: 2025
 */
namespace App\Abstracts;

use App\DTO\Store\ProductDataDTO;
use App\Models\Provisioning\SubdomainHost;
use App\Rules\DomainIsNotRegisted;
use App\Rules\FQDN;
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
        return [
            'domain' => ['nullable', 'max:255', new FQDN, new DomainIsNotRegisted, new RequiredIf(function () {
                return request()->input('domain_subdomain') == null;
            })],
            'domain_subdomain' => ['nullable', 'string', 'max:255', new DomainIsNotRegisted(true), new RequiredIf(function () {
                return request()->input('domain') == null && SubdomainHost::count() > 0;
            })],
            'subdomain' => ['nullable', 'string', 'max:255', Rule::in(SubdomainHost::all()->pluck('domain')->toArray()), new RequiredIf(function () {
                return request()->input('domain') == null && SubdomainHost::count() > 0;
            })],
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
        $subdomains = SubdomainHost::all();

        return view($this->view, [
            'productData' => $productDataDTO,
            'data' => $productDataDTO->data,
            'subdomains' => $subdomains,
        ])->render();
    }
}
