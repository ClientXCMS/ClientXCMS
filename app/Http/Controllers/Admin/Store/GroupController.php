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


namespace App\Http\Controllers\Admin\Store;

use App\Events\Resources\ResourceCloneEvent;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Store\StoreGroupRequest;
use App\Http\Requests\Store\UpdateGroupRequest;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use Illuminate\Http\Request;

class GroupController extends AbstractCrudController
{
    protected string $viewPath = 'admin.store.groups';

    protected string $routePath = 'admin.groups';

    protected string $translatePrefix = 'admin.groups';

    protected string $model = Group::class;

    protected int $perPage = 25;

    protected string $searchField = 'name';

    protected ?string $managedPermission = 'admin.manage_groups';

    public function getCreateParams()
    {
        $this->checkPermission('create');
        $data = parent::getCreateParams();
        $data['groups'] = $this->parents();

        return $data;
    }

    public function show(Group $group)
    {
        $this->checkPermission('show');
        $params['item'] = $group;
        $params['groups'] = $this->parents()->filter(function ($value, $key) use ($group) {
            return $key != $group->id;
        });

        return $this->showView($params);
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $this->checkPermission('update');
        $request->update();

        return $this->updateRedirect($group);
    }

    public function store(StoreGroupRequest $request)
    {
        $this->checkPermission('create');
        $group = $request->store();

        return $this->storeRedirect($group);
    }

    public function destroy(Group $group)
    {
        $this->checkPermission('delete');
        if ($group->products->isNotEmpty()) {
            return redirect()->back()->with('error', __($this->translatePrefix.'.groupcannotdeleted'));
        }
        $group->groups->map(function ($group) {
            $group->update(['parent_id' => null]);
        });
        $group->delete();

        return $this->deleteRedirect($group);
    }

    private function parents()
    {
        $groups = Group::parents()->get()->pluck('name', 'id');
        $groups->put('none', __('global.none'));

        return $groups;
    }

    public function clone(Request $request, Group $group)
    {

        $this->checkPermission('create');
        try {
            $newGroup = $group->replicate();
            $newGroup->name = $group->name.' - '.__('global.clone');
            $newGroup->slug = $group->slug.'-'.__('global.clone');
            while(Group::withTrashed()->where('slug', $newGroup->slug)->exists()) {
                $newGroup->slug = $newGroup->slug.'-'.rand(1000, 9999);
            }
            $newGroup->save();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        $group->groups->map(function ($group) use ($newGroup) {
            $group->update(['parent_id' => $newGroup->id]);
        });
        if ($group->image != null) {
            $filename = $newGroup->slug.'.'.pathinfo($group->image, PATHINFO_EXTENSION);
            \Storage::copy($group->image, 'public'.DIRECTORY_SEPARATOR.'groups'.DIRECTORY_SEPARATOR.$filename);
            $newGroup->update(['image' => 'groups/'.$filename]);
        }
        if ($group->getMetadata('use_image_as_background') == 'true') {
            $newGroup->attachMetadata('use_image_as_background', 'true');
        }
        if ($request->has('with_products')) {
            $products = $group->products;
            foreach ($products as $product) {
                $newProduct = $product->replicate();
                $newProduct->name = $product->name.' - Clone';
                $newProduct->group_id = $newGroup->id;
                $newProduct->save();
                $pricing = Pricing::where('related_id', $product->id)->where('related_type', 'product')->first();
                if ($pricing != null) {
                    $newPricing = $pricing->replicate();
                    $newPricing->related_id = $newProduct->id;
                    $newPricing->save();
                }
                if ($product->productType()->config() != null) {
                    $product->productType()->config()->cloneConfig($product, $newProduct);
                }
            }
        }
        PricingService::forgot();
        event(new ResourceCloneEvent($group));

        return redirect()->route($this->routePath.'.show', ['group' => $newGroup->id]);
    }

    public function sort(Request $request)
    {
        $this->checkPermission('update');
        $products = $request->items;
        $i = 0;
        foreach ($products as $id) {
            $product = Product::find($id);
            $product->sort_order = $i;
            $product->save();
            $i++;
        }

        return response()->json(['success' => true]);
    }
}
