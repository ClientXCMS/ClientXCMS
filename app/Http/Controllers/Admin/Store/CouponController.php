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
namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Store\CouponRequest;
use App\Models\Store\Coupon;
use App\Models\Store\CouponUsage;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;

class CouponController extends AbstractCrudController
{
    protected string $viewPath = 'admin.store.coupons';

    protected string $routePath = 'admin.coupons';

    protected string $translatePrefix = 'coupon.admin';

    protected string $model = Coupon::class;

    protected ?string $managedPermission = 'admin.manage_coupons';

    private array $products = [];

    private array $groups = [];

    public function show(Coupon $coupon)
    {
        $params['item'] = $coupon;
        $params['types'] = $this->types();
        $params['pricing'] = Pricing::where('related_id', $coupon->id)->where('related_type', 'coupon')->first();
        $params['products'] = $this->fetchProducts();
        $params['requiredProducts'] = $this->fetchProducts();
        $params['groups'] = $this->fetchGroups();
        $params['selectedProducts'] = $this->selectedProducts($coupon);
        $params['recurrings'] = (new RecurringService)->getRecurrings();
        $params['usages'] = $coupon->usages()->orderBy('created_at', 'desc')->paginate(25);
        if (! $coupon->is_global && $coupon->products()->count() == 0) {
            \Session::flash('warning', __('coupon.admin.no_products'));
        }
        if ($params['pricing'] == null) {
            $params['pricing'] = new Pricing;
        }
        $params['requiredProductSelected'] = $coupon->products_required;

        return $this->showView($params);
    }

    public function store(CouponRequest $request)
    {
        $coupon = new Coupon;
        $coupon->products_required = $request->input('required_products', []);
        $coupon->fill($request->only(['code', 'type', 'applied_month', 'free_setup', 'start_at', 'end_at', 'first_order_only', 'max_uses', 'max_uses_per_customer', 'usages', 'required_products', 'minimum_order_amount', 'is_global']));
        $coupon->save();
        $pricing = new Pricing;
        $pricing->related_id = $coupon->id;
        $pricing->related_type = 'coupon';
        $pricing->updateFromArray($request->only('pricing'), 'coupon');
        $coupon->products()->sync($request->input('products', []));
        PricingService::forgot();

        return $this->storeRedirect($coupon);
    }

    public function getCreateParams()
    {
        $data = parent::getCreateParams();
        $data['types'] = $this->types();
        $data['products'] = $this->fetchProducts();
        $data['requiredProducts'] = $this->fetchProducts();
        $data['groups'] = $this->fetchGroups();
        $data['pricing'] = new Pricing;
        $data['recurrings'] = (new RecurringService)->getRecurrings();
        $data['selectedProducts'] = [];

        return $data;
    }

    public function update(CouponRequest $request, Coupon $coupon)
    {
        $keys = ['code', 'type', 'applied_month', 'free_setup', 'start_at', 'end_at', 'first_order_only', 'max_uses', 'max_uses_per_customer', 'usages', 'required_products', 'minimum_order_amount', 'is_global'];
        $coupon->products_required = $request->input('required_products', []);
        $coupon->save();
        $coupon->update($request->only($keys));
        $pricing = Pricing::where('related_id', $coupon->id)->where('related_type', 'coupon')->first();
        if ($pricing == null) {
            $pricing = new Pricing;
            $pricing->related_id = $coupon->id;
            $pricing->related_type = 'coupon';
        }
        $coupon->products()->sync($request->input('products', []));
        $pricing->updateFromArray($request->only('pricing'), 'coupon');
        PricingService::forgot();
        \Cache::forget('coupon_'.$coupon->id);

        return $this->updateRedirect($coupon);
    }

    private function types()
    {
        return [
            'percent' => __('coupon.admin.percent'),
            'fixed' => __('coupon.admin.fixed'),
        ];
    }

    private function fetchProducts()
    {
        if (empty($this->products) || empty($this->groups)) {
            $this->products = Product::all()->pluck('name', 'id')->toArray();
            $this->groups = Group::all()->pluck('name', 'id')->toArray();
        }
        $data = [];
        foreach ($this->products as $id => $name) {
            $data[$id] = $name;
        }

        return $data;
    }

    private function fetchGroups()
    {
        if (empty($this->products) || empty($this->groups)) {
            $this->products = Product::all()->pluck('name', 'id')->toArray();
            $this->groups = Group::all()->pluck('name', 'id')->toArray();
        }
        $data = [];
        foreach ($this->groups as $id => $name) {
            $data[$id] = $name;
        }

        return $data;
    }

    private function selectedProducts(Coupon $coupon)
    {
        return collect($coupon->products)->map(function ($product) {
            return $product->id;
        })->toArray();
    }

    public function deleteusage(CouponUsage $couponUsage)
    {
        $couponUsage->delete();

        return redirect()->back()->with('success', __($this->flashs['deleted']));
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->pricing()->delete();
        $coupon->products()->detach();
        $coupon->usages()->delete();
        $coupon->delete();

        return $this->deleteRedirect($coupon);
    }
}
