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

use App\Core\License\LicenseGateway;
use App\Extensions\UpdaterManager;
use App\Models\Admin\Permission;
use App\Providers\AppServiceProvider;

class UpdateController
{
    public function index()
    {
        staff_aborts_permission(Permission::MANAGE_UPDATE);
        $changelogUrl = LicenseGateway::getDomain().'/changelogs';
        $changelog = \Cache::rememberForever('changelogs', function () use ($changelogUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders(['Accept' => 'application/json'])->get($changelogUrl)->throw();

                if ($response->successful()) {
                    return $response->object();
                }
            } catch (\Exception) {
                return [];
            }

            return [];
        });
        $card = app('settings')->getCards()->firstWhere('uuid', 'security');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'update');
        \View::share('current_card', $card);
        \View::share('current_item', $item);
        $currentVersion = AppServiceProvider::VERSION;
        $publishedVersions = collect($changelog)->first();

        return view('admin.security.update.index', compact('changelogUrl', 'changelog', 'currentVersion', 'publishedVersions'));
    }

    public function update()
    {
        staff_aborts_permission(Permission::MANAGE_UPDATE);
        try {
            (new UpdaterManager)->update('core');

            return back()->with('success', __('admin.update.updated_success'));
        } catch (\Exception $e) {
        }
    }
}
