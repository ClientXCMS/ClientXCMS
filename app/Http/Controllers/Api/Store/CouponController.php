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
namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Store\CouponRequest;
use App\Models\Store\Coupon;
use App\Models\Store\Pricing;
use App\Services\Store\PricingService;
use Illuminate\Http\Request;

class CouponController extends AbstractApiController
{
    protected string $model = Coupon::class;

    protected array $sorts = [
        'id',
        'code',
        'type',
        'start_at',
        'end_at',
        'max_uses',
        'usages',
        'is_global',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'products',
        'pricing',
        'usages',
        'customer',
    ];

    protected array $filters = [
        'id',
        'code',
        'type',
        'is_global',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/coupons",
     *     summary="Get a list of coupons",
     *     tags={"Coupons"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of coupons"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *         @OA\Schema(type="string", default="products,pricing")
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Post(
     *     path="/application/coupons",
     *     summary="Create a new coupon",
     *     tags={"Coupons"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "type"},
     *             @OA\Property(property="code", type="string", example="SUMMER25"),
     *             @OA\Property(property="type", type="string", enum={"fixed", "percent"}, example="percent"),
     *             @OA\Property(property="applied_month", type="integer", example=-1),
     *             @OA\Property(property="free_setup", type="boolean", example=false),
     *             @OA\Property(property="start_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="end_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="first_order_only", type="boolean", example=false),
     *             @OA\Property(property="max_uses", type="integer", example=0),
     *             @OA\Property(property="max_uses_per_customer", type="integer", example=0),
     *             @OA\Property(property="minimum_order_amount", type="number", example=0),
     *             @OA\Property(property="is_global", type="boolean", example=true),
     *             @OA\Property(property="products", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="pricing", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Coupon created successfully"
     *     )
     * )
     */
    public function store(CouponRequest $request)
    {
        $coupon = new Coupon;
        $coupon->products_required = $request->input('required_products', []);
        $coupon->fill($request->only(['code', 'customer_id', 'type', 'applied_month', 'free_setup', 'start_at', 'end_at', 'first_order_only', 'max_uses', 'max_uses_per_customer', 'usages', 'required_products', 'minimum_order_amount', 'is_global']));
        $coupon->save();
        
        if ($request->has('pricing')) {
            $pricing = new Pricing;
            $pricing->related_id = $coupon->id;
            $pricing->related_type = 'coupon';
            $pricing->updateFromArray($request->only('pricing'), 'coupon');
        }
        
        $coupon->products()->sync($request->input('products', []));
        PricingService::forgot();
        
        return response()->json($coupon->load(['products', 'pricing']), 201);
    }

    /**
     * @OA\Get(
     *     path="/application/coupons/{coupon}",
     *     summary="Get a single coupon",
     *     tags={"Coupons"},
     *     @OA\Response(
     *         response=200,
     *         description="A single coupon"
     *     ),
     *     @OA\Parameter(
     *         name="coupon",
     *         in="path",
     *         description="ID of the coupon",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function show(int $coupon)
    {
        return $this->queryShow($coupon);
    }

    /**
     * @OA\Put(
     *     path="/application/coupons/{coupon}",
     *     summary="Update an existing coupon",
     *     tags={"Coupons"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="type", type="string", enum={"fixed", "percent"}),
     *             @OA\Property(property="is_global", type="boolean"),
     *             @OA\Property(property="products", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Coupon updated successfully"
     *     ),
     *     @OA\Parameter(
     *         name="coupon",
     *         in="path",
     *         description="ID of the coupon",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function update(CouponRequest $request, Coupon $coupon)
    {
        $keys = ['code', 'type', 'applied_month', 'customer_id', 'free_setup', 'start_at', 'end_at', 'first_order_only', 'max_uses', 'max_uses_per_customer', 'usages', 'required_products', 'minimum_order_amount', 'is_global'];
        $coupon->products_required = $request->input('required_products', []);
        $coupon->update($request->only($keys));
        
        if ($request->has('pricing')) {
            $pricing = Pricing::where('related_id', $coupon->id)->where('related_type', 'coupon')->first();
            if ($pricing == null) {
                $pricing = new Pricing;
                $pricing->related_id = $coupon->id;
                $pricing->related_type = 'coupon';
            }
            $pricing->updateFromArray($request->only('pricing'), 'coupon');
        }
        
        $coupon->products()->sync($request->input('products', []));
        PricingService::forgot();
        \Cache::forget('coupon_'.$coupon->id);
        
        return response()->json($coupon->load(['products', 'pricing']), 200);
    }

    /**
     * @OA\Delete(
     *     path="/application/coupons/{coupon}",
     *     summary="Delete an existing coupon",
     *     tags={"Coupons"},
     *     @OA\Response(
     *         response=200,
     *         description="Coupon deleted successfully"
     *     ),
     *     @OA\Parameter(
     *         name="coupon",
     *         in="path",
     *         description="ID of the coupon",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->pricing()->delete();
        $coupon->products()->detach();
        $coupon->usages()->delete();
        $coupon->delete();
        
        return response()->json(['message' => 'Coupon deleted successfully'], 200);
    }
}
