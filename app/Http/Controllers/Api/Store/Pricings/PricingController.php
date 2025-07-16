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
namespace App\Http\Controllers\Api\Store\Pricings;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Store\PricingRequest;
use App\Http\Resources\Store\PricingCollection;
use App\Models\Store\Pricing;
use Illuminate\Http\Request;

class PricingController extends AbstractApiController
{
    protected array $sorts = [
        'id',
        'related_id',
        'related_type',
        'currency',
        'onetime',
        'monthly',
        'quarterly',
        'semiannually',
        'annually',
        'biennially',
        'triennially',
        'setup_onetime',
        'setup_monthly',
        'setup_quarterly',
        'setup_semiannually',
        'setup_annually',
        'setup_biennially',
        'setup_triennially',
    ];

    protected array $filters = [
        'id',
        'related_id',
        'related_type',
        'currency',
        'onetime',
        'monthly',
        'quarterly',
        'semiannually',
        'annually',
        'biennially',
        'triennially',
        'setup_onetime',
        'setup_monthly',
        'setup_quarterly',
        'setup_semiannually',
        'setup_annually',
        'setup_biennially',
        'setup_triennially',
    ];

    protected string $model = Pricing::class;

    /**
     * @OA\Get(
     *     path="/application/pricings",
     *     operationId="getPricings",
     *     tags={"Products"},
     *     summary="List all pricings",
     *     description="Returns a paginated list of pricing entries with optional filtering and sorting.",
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page",
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[related_id]",
     *         in="query",
     *         description="Filter by related ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[related_type]",
     *         in="query",
     *         description="Filter by related type (e.g. product, config_option)",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[currency]",
     *         in="query",
     *         description="Filter by currency",
     *
     *         @OA\Schema(type="string", example="EUR")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[monthly]",
     *         in="query",
     *         description="Filter by monthly price ou can replace this with any other pricing field",
     *
     *         @OA\Schema(type="number", format="float")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort[]",
     *         in="query",
     *         description="Sort fields (use - for descending)",
     *
     *         @OA\Schema(type="array", @OA\Items(type="string", example="monthly"))
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopPricing")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        return new PricingCollection($this->queryIndex($request));
    }

    /**
     * @OA\Post(
     *     path="/application/pricings",
     *     operationId="createPricing",
     *     tags={"Products"},
     *     summary="Create a pricing",
     *     description="Creates a new pricing entry for a product or config option",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PricingRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Pricing created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopPricing")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(PricingRequest $request)
    {
        $params = $request->validated();
        $item = Pricing::create($params);

        return response()->json($item, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/pricings/{id}",
     *     operationId="getPricing",
     *     tags={"Products"},
     *     summary="Get a specific pricing",
     *     description="Returns the pricing identified by id",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopPricing")
     *     ),
     *
     *     @OA\Response(response=404, description="Pricing not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(Pricing $pricing)
    {
        return response()->json($pricing, 200);
    }

    /**
     * @OA\Post(
     *     path="/application/pricings/{id}",
     *     operationId="updatePricing",
     *     tags={"Products"},
     *     summary="Update pricing",
     *     description="Updates an existing pricing record",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PricingRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pricing updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopPricing")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(PricingRequest $request, Pricing $pricing)
    {
        $params = $request->validated();
        $pricing->update($params);

        return response()->json($pricing);
    }

    /**
     * @OA\Delete(
     *     path="/application/pricings/{id}",
     *     operationId="deletePricing",
     *     tags={"Products"},
     *     summary="Delete a pricing",
     *     description="Deletes a pricing from the system",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pricing deleted successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopPricing")
     *     ),
     *
     *     @OA\Response(response=404, description="Pricing not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Pricing $pricing)
    {
        $pricing->delete();

        return response()->json($pricing, 200);
    }
}
