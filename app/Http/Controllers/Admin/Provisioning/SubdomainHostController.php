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

namespace App\Http\Controllers\Admin\Provisioning;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Admin\Permission;
use App\Models\Provisioning\SubdomainHost;
use App\Models\Store\Group;
use App\Models\Store\Product;
use App\Rules\FQDN;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubdomainHostController extends AbstractCrudController
{
    protected string $model = SubdomainHost::class;

    protected string $viewPath = 'admin.provisioning.subdomains_hosts';

    protected string $routePath = 'admin.subdomains_hosts';

    protected string $translatePrefix = 'provisioning.admin.subdomains_hosts';

    protected ?string $managedPermission = Permission::MANAGE_SUBDOMAINS_HOSTS;

    public function getIndexParams($items, string $translatePrefix, $filter = null, $filters = [])
    {
        $this->shareSettingsCard();

        return parent::getIndexParams($items, $translatePrefix, $filter, $filters);
    }

    public function show(SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission(Permission::MANAGE_SUBDOMAINS_HOSTS);

        $this->shareSettingsCard();

        return $this->showView([
            'item' => $subdomainsHost,
            'products' => Product::getAllProducts(true),
            'groups' => Group::getAvailable(true)->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SUBDOMAINS_HOSTS);
        $data = $this->validateSubdomainHost($request);
        $subdomain = SubdomainHost::create($data);

        return $this->storeRedirect($subdomain);
    }

    private function validateSubdomainHost(Request $request, ?SubdomainHost $subdomainHost = null): array
    {
        $unique = Rule::unique('subdomains_hosts', 'domain');
        if ($subdomainHost != null) {
            $unique->ignore($subdomainHost->id);
        }

        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                $unique,
                'max:255',
                new FQDN,
            ],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', Rule::exists('products', 'id')],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', Rule::exists('groups', 'id')],
        ]);

        $data['products'] = array_values(array_map('intval', $data['products'] ?? []));
        $data['groups'] = array_values(array_map('intval', $data['groups'] ?? []));

        return $data;
    }

    public function update(Request $request, SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission(Permission::MANAGE_SUBDOMAINS_HOSTS);
        $data = $this->validateSubdomainHost($request, $subdomainsHost);
        $subdomainsHost->update($data);

        return $this->updateRedirect($subdomainsHost);
    }

    public function destroy(SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission(Permission::MANAGE_SUBDOMAINS_HOSTS);
        $subdomainsHost->delete();

        return $this->deleteRedirect($subdomainsHost);
    }

    public function getCreateParams()
    {
        $this->shareSettingsCard();
        $params = parent::getCreateParams();
        $params['products'] = Product::getAllProducts(true);
        $params['groups'] = Group::getAvailable(true)->pluck('name', 'id');

        return $params;
    }

    private function shareSettingsCard(): void
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'subdomains_hosts');
        \View::share('current_card', $card);
        \View::share('current_item', $item);
    }
}
