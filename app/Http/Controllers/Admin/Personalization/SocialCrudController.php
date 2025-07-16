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
namespace App\Http\Controllers\Admin\Personalization;

use App\Events\Resources\ResourceUpdatedEvent;
use App\Models\Personalization\SocialNetwork;
use App\Theme\ThemeManager;
use Illuminate\Http\Request;

class SocialCrudController extends \App\Http\Controllers\Admin\AbstractCrudController
{
    protected string $viewPath = 'admin.personalization.socials';

    protected string $routePath = 'admin.personalization.socials';

    protected string $translatePrefix = 'personalization.social';

    protected string $model = SocialNetwork::class;

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);
        $model = $this->model::create($data);
        ThemeManager::clearCache();

        return $this->storeRedirect($model);
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

        return $this->updateRedirect($social);
    }

    public function getCreateParams()
    {
        $params = parent::getCreateParams();

        $params['current_card'] = app('settings')->getCurrentCard('personalization');
        $params['current_item'] = app('settings')->getCurrentItem('personalization', 'social');

        return $params;
    }

    public function showView(array $params)
    {
        $params = parent::showView($params);
        $params['current_card'] = app('settings')->getCurrentCard('personalization');
        $params['current_item'] = app('settings')->getCurrentItem('personalization', 'social');

        return $params;
    }

    public function show(SocialNetwork $social)
    {
        return $this->showView([
            'item' => $social,
        ]);
    }

    public function destroy(SocialNetwork $social)
    {
        $social->delete();
        ThemeManager::clearCache();

        event(new ResourceUpdatedEvent($social));
        return back()->with('success', __($this->flashs['deleted']));
    }
}
