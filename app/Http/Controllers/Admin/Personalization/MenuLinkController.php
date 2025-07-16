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

use App\Events\Resources\ResourceCreatedEvent;
use App\Events\Resources\ResourceUpdatedEvent;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Personalization\MenuLinkRequest;
use App\Models\Admin\Permission;
use App\Models\Personalization\MenuLink;
use App\Theme\ThemeManager;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuLinkController extends AbstractCrudController
{
    protected string $model = MenuLink::class;

    protected string $translatePrefix = 'personalization.menu_links';

    protected string $viewPath = 'admin.personalization.menu_links';

    protected string $routePath = 'admin.personalization.menulinks';

    protected ?string $managedPermission = Permission::MANAGE_PERSONALIZATION;

    public function index(Request $request)
    {
        abort(404);
    }

    public function create(Request $request)
    {
        $type = $request->type;
        abort_if(! in_array($type, ['front', 'bottom']), 404);
        $data = $this->getArray($type);
        $data['type'] = $type;

        return $this->createView($data);
    }

    public function store(MenuLinkRequest $request)
    {
        $type = $request->type;
        abort_if(! in_array($type, ['front', 'bottom']), 404);
        $this->checkPermission('create');
        $validated = $request->validated();
        $validated['position'] = MenuLink::where('type', $type)->count();
        $validated['type'] = $type;
        $menulink = MenuLink::create($validated);
        ThemeManager::clearCache();

        return $this->storeRedirect($menulink);
    }

    public function sort(Request $request, string $type)
    {
        $this->checkPermission('update');
        if (! in_array($type, ['front', 'bottom'])) {
            return new Response(404);
        }

        $menuLinks = $request->items;
        $i = 0;
        foreach ($menuLinks as $menuLink) {
            if (is_array($menuLink)) {
                $id = $menuLink['id'];
                $children = $menuLink['children'];
            } else {
                $id = $menuLink;
                $children = [];
            }
            $menu = MenuLink::find($id);
            $menu->update([
                'position' => $i,
                'type' => $type,
                'parent_id' => null,
            ]);
            foreach ($children as $child) {
                $menu = MenuLink::find($child);
                $menu->update([
                    'position' => $i,
                    'type' => $type,
                    'parent_id' => $id,
                ]);
                $i++;
            }
            $i++;
        }
        ThemeManager::clearCache();

        return response()->json(['success' => true]);
    }

    public function show(Request $request, MenuLink $menulink)
    {
        $this->checkPermission('show');
        abort_if(! in_array($menulink->type, ['front', 'bottom']), 404);
        $data = $this->getArray($menulink->type, $menulink->id, $menulink->parent_id);

        return $this->showView($data + ['item' => $menulink]);
    }

    public function update(MenuLinkRequest $request, MenuLink $menulink)
    {
        $this->checkPermission('update');

        $menulink->update($request->validated());
        ThemeManager::clearCache();

        return $this->updateRedirect($menulink);
    }

    public function delete(Request $request, MenuLink $menulink)
    {
        $this->checkPermission('delete');
        $menulink->delete();
        ThemeManager::clearCache();

        return $this->deleteRedirect($menulink);
    }

    public function getArray(string $type, ?int $menuId = null, ?int $parentId = null): array
    {
        $supported = app('theme')->getTheme()->supportOption($this->getOptionName($type));
        if ($menuId) {
            $menuLinks = MenuLink::where('type', $type)->whereNull('parent_id')->where('id', '!=', $menuId)->orderBy('position');
        } else {
            $menuLinks = MenuLink::where('type', $type)->whereNull('parent_id')->orderBy('position');
        }
        $card = app('settings')->getCards()->firstWhere('uuid', 'personalization');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', $type.'_menu');
        $data = [
            'type' => $type,
            'roles' => [
                'all' => __($this->translatePrefix.'.allowed_roles.all'),
                'staff' => __($this->translatePrefix.'.allowed_roles.staff'),
                'customer' => __($this->translatePrefix.'.allowed_roles.customer'),
                'logged' => __($this->translatePrefix.'.allowed_roles.logged'),
            ],
            'linkTypes' => [
                'link' => __($this->translatePrefix.'.link'),
                'new_tab' => __($this->translatePrefix.'.new_tab'),
            ],
            'menus' => $menuLinks->get()->pluck('name', 'id'),
            'current_card' => $card,
            'current_item' => $item,
            'supportDropDropdown' => $supported,
        ];
        if ($type == 'front' && $supported && ! $parentId) {
            $data['linkTypes']['dropdown'] = __($this->translatePrefix.'.dropdown');
        }
        if ($data['menus']->count() > 0 && $supported) {
            $data['menus']->put('none', __('global.none'));
        }

        return $data;
    }

    public function getOptionName(string $type): string
    {
        if ($type == 'front') {
            return 'menu_dropdown';
        }
        if ($type == 'bottom') {
            return 'multi_footer_columns';
        }

        return '';
    }

    public function updateRedirect(Model $model)
    {

        event(new ResourceUpdatedEvent($model));
        $type = $model->type;
        if (! in_array($type, ['front', 'bottom'])) {
            return redirect()->back()->with('success', __($this->flashs['updated']));
        }
        $method = $type.'_menu';

        return redirect()->route('admin.settings.show', ['card' => 'personalization', 'uuid' => $method])->with('success', __($this->flashs['updated']));
    }

    public function storeRedirect(Model $model)
    {
        event(new ResourceCreatedEvent($model));
        $type = $model->type;
        if (! in_array($type, ['front', 'bottom'])) {
            return redirect()->back()->with('success', __($this->flashs['created']));
        }
        $method = $type.'_menu';

        return redirect()->route('admin.settings.show', ['card' => 'personalization', 'uuid' => $method])->with('success', __($this->flashs['created']));
    }

    public function deleteRedirect(Model $model)
    {
        event(new ResourceUpdatedEvent($model));
        $type = $model->type;
        if (! in_array($type, ['front', 'bottom'])) {
            return redirect()->back()->with('success', __($this->flashs['deleted']));
        }
        $method = $type.'_menu';

        return redirect()->route('admin.settings.show', ['card' => 'personalization', 'uuid' => $method])->with('success', __($this->flashs['deleted']));
    }
}
