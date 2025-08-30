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
use App\Models\Provisioning\SubdomainHost;
use App\Rules\FQDN;
use Illuminate\Http\Request;

class SubdomainHostController extends AbstractCrudController
{
    protected string $model = SubdomainHost::class;

    protected string $viewPath = 'admin.provisioning.subdomains_hosts';

    protected string $routePath = 'admin.subdomains_hosts';

    protected string $translatePrefix = 'provisioning.admin.subdomains_hosts';

    public function getIndexParams($items, string $translatePrefix, $filter = null, $filters = [])
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'subdomains_hosts');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return parent::getIndexParams($items, $translatePrefix, $filter, $filters);
    }

    public function show(SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission('admin.manage_subdomains_hosts');

        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'subdomains_hosts');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return $this->showView([
            'item' => $subdomainsHost,
        ]);
    }

    public function store(Request $request)
    {
        staff_aborts_permission('admin.manage_subdomains_hosts');
        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                'unique:subdomains_hosts',
                'max:255',
                new FQDN
            ],
        ]);
        $subdomain = SubdomainHost::create($data);

        return $this->storeRedirect($subdomain);
    }

    public function update(Request $request, SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission('admin.manage_subdomains_hosts');
        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                new FQDN,
                'unique:subdomains_hosts,domain,'.$subdomainsHost->id,
            ],
        ]);
        $subdomainsHost->update($data);

        return $this->updateRedirect($subdomainsHost);
    }

    public function destroy(SubdomainHost $subdomainsHost)
    {
        staff_aborts_permission('admin.manage_subdomains_hosts');
        $subdomainsHost->delete();

        return $this->deleteRedirect($subdomainsHost);
    }

    public function getCreateParams()
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'subdomains_hosts');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return parent::getCreateParams();
    }
}
