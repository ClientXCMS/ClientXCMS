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

namespace App\Http\Controllers\Api\Store\Products;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Store\StoreProductRequest;
use App\Http\Requests\Store\UpdateProductRequest;
use App\Http\Resources\Store\ProductCollection;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Http\Request;

class ProductController extends AbstractApiController
{
    protected string $model = Product::class;

    protected array $with = [
        'metadata',
        'pricing',
        'group',
    ];

    protected array $sorts = [
        'id',
        'name',
        'status',
        'description',
        'sort_order',
        'group_id',
        'stock',
        'type',
        'pinned',
    ];

    protected array $filters = [
        'id',
        'name',
        'status',
        'description',
        'sort_order',
        'group_id',
        'stock',
        'type',
        'pinned',
    ];

    protected array $relations = [
        'metadata',
        'pricing',
        'group',
    ];

    /**
     * @OA\Get(
     *      path="/application/products",
     *      operationId="getProductList",
     *      tags={"Products"},
     *      summary="Get list of products",
     *      description="Returns list of products with optional filters, sorting, relations and pagination.",
     *
     *      @OA\Parameter(
     *          name="filter[id]",
     *          in="query",
     *          description="Filter by product ID",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[name]",
     *          in="query",
     *          description="Filter by product name",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[status]",
     *          in="query",
     *          description="Filter by product status",
     *
     *          @OA\Schema(type="string", enum={"active","hidden","unreferenced"})
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[description]",
     *          in="query",
     *          description="Filter by description",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[group_id]",
     *          in="query",
     *          description="Filter by group ID",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[stock]",
     *          in="query",
     *          description="Filter by stock",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[type]",
     *          in="query",
     *          description="Filter by product type",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[pinned]",
     *          in="query",
     *          description="Filter by pinned status",
     *
     *          @OA\Schema(type="boolean")
     *      ),
     *
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sort field (e.g. sort=name or sort=-name for descending)",
     *
     *          @OA\Schema(type="string", enum={"id","name","status","description","sort_order","group_id","stock","type","pinned"})
     *      ),
     *
     *      @OA\Parameter(
     *          name="include",
     *          in="query",
     *          description="Include relations (e.g. metadata, pricing, group)",
     *
     *          @OA\Schema(type="string", example="metadata,pricing,group")
     *      ),
     *
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page",
     *
     *          @OA\Schema(type="integer", default=12)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="List of products"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = $this->queryIndex($request);

        return new ProductCollection($query);
    }

    /**
     * @OA\Post(
     *     path="/application/products",
     *     operationId="storeProduct",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     description="Creates a new product and its pricing.",
     *
     *       @OA\RequestBody(
     *           required=true,
     *
     *           @OA\JsonContent(ref="#/components/schemas/ShopProductRequest")
     *       ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopProduct")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $params = $request->validated();
        $item = Product::create($params);

        return response()->json($item, 201);
    }

    /**
     * @OA\Get(
     *      path="/application/products/{id}",
     *      operationId="getProductById",
     *      tags={"Products"},
     *      summary="Get product information",
     *      description="Returns product data",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="product id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *       @OA\Parameter(
     *           name="include",
     *           in="query",
     *           description="Include relations (e.g. metadata, pricing, group)",
     *
     *           @OA\Schema(type="string", example="metadata,pricing,group")
     *       ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ShopProduct")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="product not found"
     *      ),
     * )
     */
    public function show(int $product)
    {
        return response()->json($this->queryShow($product), 200);
    }

    /**
     * @OA\Post(
     *     path="/application/products/{id}",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     summary="Update product by ID",
     *     description="Updates product details including pricing and image.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to update",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopProductRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopProduct")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product = $request->update();

        return response()->json($product);
    }

    /**
     * @OA\Delete(
     *     path="/application/products/{id}",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     summary="Delete product by ID",
     *     description="Deletes the specified product.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to delete",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShopProduct")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json($product, 200);
    }

    /**
     * @OA\Get(
     *     path="/application/products/{id}/config",
     *     operationId="getProductConfig",
     *     tags={"Products"},
     *     summary="Get product configuration",
     *     description="Returns the product configuration as JSON, including metadata and pricing.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product configuration"
     *     ),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function config(Product $product)
    {
        $config = $product->productType()->config();
        if ($config == null) {
            return response()->json(null, 404);
        }
        $config = $config->getConfig($product->id);

        return response()->json($config, 200);
    }

    /**
     * @OA\Put(
     *     path="/application/products/{id}/config",
     *     operationId="updateProductConfig",
     *     tags={"Products"},
     *     summary="Update product configuration",
     *     description="Updates the product configuration.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Configuration updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Product config not found")
     * )
     */
    public function updateConfig(\App\Http\Requests\Store\ConfigProductRequest $request, Product $product)
    {
        $config = $product->productType()->config();
        if ($config == null) {
            return response()->json(['message' => __('admin.products.config.notfound')], 404);
        }

        if ($config->getConfig($product->id) == null) {
            $config->storeConfig($product, $request->validated());
        } else {
            $config->updateConfig($product, $request->validated());
        }

        return response()->json([
            'message' => __('admin.products.config.success'),
            'config' => $config->getConfig($product->id),
        ], 200);
    }
}
