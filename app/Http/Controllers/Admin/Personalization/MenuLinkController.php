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
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Personalization\BulkMenuItemsRequest;
use App\Http\Requests\Personalization\MenuLinkRequest;
use App\Models\Admin\Permission;
use App\Models\Personalization\MenuLink;
use App\Theme\ThemeManager;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $data = $this->getArray($type);
        $data['type'] = $type;

        $menus = MenuLink::where('type', $type)->whereNull('parent_id')->orderBy('position')->get();
        return $this->createView($data);
    }

    public function store(MenuLinkRequest $request)
    {
        $type = $request->type;
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

    /**
     * Bulk update all menu items for a given type.
     * Handles create, update, and delete operations in a single transaction.
     */
    public function bulkUpdate(BulkMenuItemsRequest $request, string $type): JsonResponse
    {
        $this->checkPermission('update');

        DB::beginTransaction();
        try {
            $items = $request->validated()['items'];
            $processedIds = [];
            $position = 0;

            $this->processMenuItems($items, $type, null, $position, $processedIds);

            // Delete items that were not in the payload (removed by user)
            MenuLink::where('type', $type)
                ->whereNotIn('id', $processedIds)
                ->delete();

            DB::commit();
            ThemeManager::clearCache();

            return response()->json([
                'success' => true,
                'message' => __('personalization.menu_links.bulk_saved'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Recursively process menu items (max 3 levels).
     *
     * @param array $items Items to process
     * @param string $type Menu type (front/bottom)
     * @param int|null $parentId Parent menu item ID
     * @param int &$position Current position counter (passed by reference)
     * @param array &$processedIds Collected IDs of processed items
     * @param int $depth Current recursion depth
     */
    private function processMenuItems(
        array $items,
        string $type,
        ?int $parentId,
        int &$position,
        array &$processedIds,
        int $depth = 0
    ): void {
        // Safety limit: max 3 levels deep
        if ($depth >= 3) {
            return;
        }

        foreach ($items as $itemData) {
            // Skip deleted items
            if (! empty($itemData['isDeleted'])) {
                if (! empty($itemData['id'])) {
                    MenuLink::where('id', $itemData['id'])->delete();
                }
                continue;
            }

            $menuData = [
                'name' => $itemData['name'],
                'url' => $itemData['url'],
                'icon' => $itemData['icon'] ?? null,
                'badge' => $itemData['badge'] ?? null,
                'description' => $itemData['description'] ?? null,
                'link_type' => $itemData['link_type'],
                'allowed_role' => $itemData['allowed_role'],
                'type' => $type,
                'parent_id' => $parentId,
                'position' => $position,
            ];

            if (! empty($itemData['id'])) {
                // Update existing item
                $menuLink = MenuLink::find($itemData['id']);
                if ($menuLink) {
                    $menuLink->update($menuData);
                    $processedIds[] = $menuLink->id;
                }
            } else {
                // Create new item
                $menuLink = MenuLink::create($menuData);
                $processedIds[] = $menuLink->id;
            }

            $position++;

            // Process translations if provided
            if (! empty($itemData['translations']) && isset($menuLink)) {
                $this->processItemTranslations($menuLink, $itemData['translations']);
            }

            // Recursively process children
            if (! empty($itemData['children']) && isset($menuLink)) {
                $this->processMenuItems(
                    $itemData['children'],
                    $type,
                    $menuLink->id,
                    $position,
                    $processedIds,
                    $depth + 1
                );
            }
        }
    }

    /**
     * Process translations for a menu item.
     */
    private function processItemTranslations(MenuLink $menuLink, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $key => $value) {
                if ($value !== null && $value !== '') {
                    $menuLink->saveTranslation($key, $locale, $value);
                }
            }
        }
    }

    public function show(Request $request, MenuLink $menulink)
    {
        $this->checkPermission('show');
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
