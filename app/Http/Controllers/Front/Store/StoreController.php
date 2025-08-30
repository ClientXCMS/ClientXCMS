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


namespace App\Http\Controllers\Front\Store;

use App\Http\Controllers\Controller;
use App\Models\Personalization\Translation;
use App\Models\Store\Group;
use App\Models\Store\Product;
use App\Services\Core\LocaleService;

class StoreController extends Controller
{
    public function index()
    {
        $groups = Group::getAvailable()->whereNull('parent_id')->orderBy('sort_order')->with('products')->get();
        $subtitle = trans('store.subtitle');
        $title = trans('store.title');
        $products = collect();

        return view('front.store.index', compact('products', 'groups', 'title', 'subtitle'));
    }

    public function group($group)
    {
        $group = $this->findGroup($group);
        $this->checkGroup($group);
        $groups = Group::getAvailable()->orderBy('sort_order')->with('products')->where('parent_id', $group->id)->get();
        $subtitle = $group->trans('description');
        $title = $group->trans('name');
        $products = $group->products()->with('metadata')->orderBy('sort_order')->get();
        $products = collect($products)->filter(function (Product $product) {
            return $product->isValid();
        });
        if ($group->parent_id != null) {
            return redirect($group->route());
        }
        if ($products->count() == 0 && $groups->count() == 0) {
            \Session::flash('info', __('store.product.noproduct'));
        }
        \View::share('meta_append', '<meta name="description" content="'.$subtitle.'">');

        return view('front.store.index', compact('group', 'groups', 'title', 'subtitle', 'products'));
    }

    public function subgroup($group, $subgroup)
    {
        $subgroup = $this->findGroup($subgroup);
        abort_if($subgroup == null, 404);
        $group = $this->findGroup($group);
        $subtitle = $subgroup->trans('description');
        $title = $subgroup->trans('name');
        $this->checkGroup($subgroup);
        $this->checkGroup($group);
        $products = Product::getAvailable()->orderBy('sort_order')->where('group_id', $subgroup->id)->get();
        $groups = Group::getAvailable()->orderBy('sort_order')->where('parent_id', $subgroup->id)->get();
        if ($products->count() == 0 && $groups->count() == 0) {
            \Session::flash('info', __('store.product.noproduct'));
        }
        \View::share('meta_append', '<meta name="description" content="'.$subtitle.'">');

        return view('front.store.group', compact('group', 'title', 'subtitle', 'products', 'groups', 'subgroup'));
    }

    private function checkGroup(Group $group)
    {
        if ($group->status == 'hidden') {
            abort(404);
        }
        if ($group->status == 'unreferenced' && ! auth('admin')->check()) {
            abort(404);
        }
    }

    private function findGroup($slug)
    {
        $group = Group::where('slug', $slug)->first();
        if ($group == null) {
            $translation = Translation::where('key', 'slug')->where('content', $slug)->where('model', Group::class)->first();
            abort_if($translation == null, 404);
            $group = Group::find($translation->model_id);
            if ($translation->locale != app()->getLocale()) {
                LocaleService::saveLocale($translation->locale);
            }
        }

        return $group;
    }
}
