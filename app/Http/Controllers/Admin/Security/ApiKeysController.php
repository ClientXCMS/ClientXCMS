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

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\RequiredIf;

class ApiKeysController extends AbstractCrudController
{
    public static function getAbilities()
    {
        return [
            __('admin.customers.title') => [
                'customers:index' => __('global.listing'),
                'customers:store' => __('global.store'),
                'customers:show' => __('global.show'),
                'customers:update' => __('global.update'),
                'customers:delete' => __('global.delete'),
            ],
            __('global.products') => [
                'products:index' => __('global.listing'),
                'products:store' => __('global.store'),
                'products:show' => __('global.show'),
                'products:update' => __('global.update'),
                'products:delete' => __('global.delete'),
            ],
            __('admin.groups.title') => [
                'groups:index' => __('global.listing'),
                'groups:store' => __('global.store'),
                'groups:show' => __('global.show'),
                'groups:update' => __('global.update'),
                'groups:delete' => __('global.delete'),
            ],
            __('admin.products.tariff') . 's' => [
                'pricing:index' => __('global.listing'),
                'pricing:store' => __('global.store'),
                'pricing:show' => __('global.show'),
                'pricing:update' => __('global.update'),
                'pricing:delete' => __('global.delete'),
            ],
            __('global.invoices') => [
                'invoices:index' => __('global.listing'),
                'invoices:store' => __('global.store'),
                'invoices:show' => __('global.show'),
                'invoices:update' => __('global.update'),
                'invoices:delete' => __('global.delete'),
            ],
            __('coupon.coupons') => [
                'coupons:index' => __('global.listing'),
                'coupons:store' => __('global.store'),
                'coupons:show' => __('global.show'),
                'coupons:update' => __('global.update'),
                'coupons:delete' => __('global.delete'),
            ],
            __('provisioning.admin.servers.title') => [
                'servers:index' => __('global.listing'),
                'servers:store' => __('global.store'),
                'servers:show' => __('global.show'),
                'servers:update' => __('global.update'),
                'servers:delete' => __('global.delete'),
                'servers:test' => __('global.test'),
            ],
            __('provisioning.admin.subdomains_hosts.title') => [
                'subdomains:index' => __('global.listing'),
                'subdomains:store' => __('global.store'),
                'subdomains:show' => __('global.show'),
                'subdomains:update' => __('global.update'),
                'subdomains:delete' => __('global.delete'),
            ],
            __('helpdesk.admin.departments.title') => [
                'departments:index' => __('global.listing'),
                'departments:store' => __('global.store'),
                'departments:show' => __('global.show'),
                'departments:update' => __('global.update'),
                'departments:delete' => __('global.delete'),
            ],
            __('global.tickets') => [
                'tickets:index' => __('global.listing'),
                'tickets:store' => __('global.store'),
                'tickets:show' => __('global.show'),
                'tickets:update' => __('global.update'),
                'tickets:delete' => __('global.delete'),
                'tickets:reply' => __('helpdesk.support.show.reply'),
                'tickets:close' => __('helpdesk.support.show.close'),
                'tickets:reopen' => __('helpdesk.support.show.reopen'),
            ],
            __('global.services') => [
                'services:index' => __('global.listing'),
                'services:store' => __('global.store'),
                'services:show' => __('global.show'),
                'services:update' => __('global.update'),
                'services:delete' => __('global.delete'),
                'services:suspend' => __('global.suspend'),
                'services:unsuspend' => __('global.unsuspend'),
                'services:terminate' => __('global.terminate'),
            ],
            __('actionslog.settings.title') => [
                'actionlogs:index' => __('global.listing'),
                'actionlogs:show' => __('global.show'),
            ],
        ];
    }

    public function index(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_API_KEYS);
        $card = app('settings')->getCards()->firstWhere('uuid', 'security');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'apikeys');

        return view('admin.security.apikeys.index', [
            'translatePrefix' => 'admin.api_keys',
            'routePath' => 'admin.api-keys',
            'items' => auth('admin')->user()->tokens()->get(),
            'current_card' => $card,
            'current_item' => $item,
        ]);
    }

    public function create(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_API_KEYS);
        $card = app('settings')->getCards()->firstWhere('uuid', 'security');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'apikeys');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return view('admin.security.apikeys.create', [
            'abilities' => self::getAbilities(),
            'translatePrefix' => 'admin.api_keys',
            'routePath' => 'admin.api-keys',
        ]);
    }

    public function store(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_API_KEYS);
        $validated = $request->validate([
            'name' => 'required|max:200',
            'permissions' => [new RequiredIf(! array_key_exists('is_admin', $request->all())), 'array'],
            'expires_at' => 'nullable|date',
            'is_admin' => 'nullable',
        ]);
        if (array_key_exists('is_admin', $validated)) {
            $validated['permissions'] = ['*'];
        } else {
            $validated['permissions'] = array_merge(array_keys($validated['permissions']), ['hearth', 'license']);
        }
        $token = auth('admin')->user()->createToken($validated['name'], $validated['permissions']);

        return redirect()->route('admin.api-keys.index')->with('success', __('admin.api_keys.created', ['name' => $validated['name'], 'key' => $token->plainTextToken]));
    }

    public function destroy($id)
    {
        staff_aborts_permission(Permission::MANAGE_API_KEYS);
        $token = auth('admin')->user()->tokens()->findOrFail($id);
        $token->delete();

        return redirect()->route('admin.api-keys.index')->with('success', __($this->flashs['deleted']));
    }

    public function rotate($id)
    {
        staff_aborts_permission(Permission::MANAGE_API_KEYS);
        $token = auth('admin')->user()->tokens()->findOrFail($id);
        $newToken = auth('admin')->user()->createToken($token->name, $token->abilities);
        $token->delete();

        return redirect()->route('admin.api-keys.index')->with('success', __('admin.api_keys.created', ['name' => $token->name, 'key' => $newToken->plainTextToken]));
    }
}
