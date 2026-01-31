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

namespace App\Http\Controllers\Admin\Personalization;

use App\Events\Resources\ResourceCreatedEvent;
use App\Events\Resources\ResourceUpdatedEvent;
use App\Models\Personalization\SocialNetwork;
use App\Theme\ThemeManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialCrudController extends \App\Http\Controllers\Admin\AbstractCrudController
{
    protected string $viewPath = 'admin.personalization.socials';

    protected string $routePath = 'admin.personalization.socials';

    protected string $translatePrefix = 'personalization.social';

    protected string $model = SocialNetwork::class;

    protected function queryIndex(): LengthAwarePaginator
    {
        return SocialNetwork::orderBy('position')
            ->paginate($this->perPage)
            ->appends(request()->query());
    }

    protected function getIndexParams($items, string $translatePrefix)
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $params['current_card'] = app('settings')->getCurrentCard('personalization');
        $params['current_item'] = app('settings')->getCurrentItem('personalization', 'social');

        return $params;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);
        $data['position'] = SocialNetwork::max('position') + 1;
        $model = $this->model::create($data);
        ThemeManager::clearCache();

        event(new ResourceCreatedEvent($model));

        return redirect()->route($this->routePath . '.index')
            ->with('success', __($this->flashs['created']));
    }

    /**
     * Reorder social networks via drag-and-drop.
     */
    public function sort(Request $request)
    {
        $this->checkPermission('update');

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer|exists:theme_socialnetworks,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $position => $id) {
                SocialNetwork::where('id', $id)->update(['position' => $position]);
            }
        });

        ThemeManager::clearCache();

        return response()->json(['success' => true]);
    }

    public function update(Request $request, SocialNetwork $social)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);
        $social->update($data);
        ThemeManager::clearCache();

        event(new ResourceUpdatedEvent($social));

        return redirect()->route($this->routePath . '.index')
            ->with('success', __($this->flashs['updated']));
    }

    public function create(Request $request)
    {
        return redirect()->route($this->routePath . '.index');
    }

    public function show(SocialNetwork $social)
    {
        return redirect()->route($this->routePath . '.index');
    }

    public function destroy(SocialNetwork $social)
    {
        $social->delete();
        ThemeManager::clearCache();

        event(new ResourceUpdatedEvent($social));

        return back()->with('success', __($this->flashs['deleted']));
    }
}
