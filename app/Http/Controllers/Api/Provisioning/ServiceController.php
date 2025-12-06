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


namespace App\Http\Controllers\Api\Provisioning;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Resources\Provisioning\ServiceCollection;
use App\Models\Provisioning\Service;
use App\Services\Provisioning\ServiceService;
use Illuminate\Http\Request;

class ServiceController extends AbstractApiController
{
    protected array $sorts = [
        'id',
        'customer_id',
        'status',
        'created_at',
        'updated_at',
    ];
    protected array $relations = [
        'customer',
        'metadata',
        'pricings',
        'configoptions'
    ];
    protected array $filters = [
        'id',
        'customer_id',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/services",
     *     operationId="getAllServices",
     *     tags={"Services"},
     *     summary="Get a list of services",
     *     description="Returns a list of services",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number",
     *          required=false,
     *          @OA\Schema(type="integer", default=1)
     *      ),
     *      @OA\Parameter(
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
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter services",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *         @OA\Schema(type="string", default="customer,metadata,pricings,configoptions")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = $this->queryIndex($request);
        return new ServiceCollection($query);
    }

    /**
     * @OA\Get(
     *      path="/application/services/{id}",
     *      operationId="getServiceById",
     *      tags={"Services"},
     *      summary="Get service information",
     *      description="Returns service data",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="service id or uuid",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ProvisioningService")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="service not found"
     *      ),
     * )
     */
    public function show(Service $service)
    {
        return $this->queryShow($service->id);
    }

    /**
     * @OA\delete(
     *      path="/application/expire/{id}",
     *      operationId="expireServiceById",
     *      tags={"Services"},
     *      summary="expire service",
     *      description="Returns service data and result",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="service id or uuid",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="force",
     *                     type="boolean"
     *                 ),
     *                 example={"force": false}
     *             )
     *         )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ProvisioningService")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="service not found"
     *      ),
     * )
     */
    public function expire(Request $request, Service $service)
    {
        $result = ServiceService::changeServiceStatus($request, $service, 'expired');
        return response()->json(['data' => $service, 'result' => $result]);
    }

    /**
     * @OA\Put(
     *      path="/application/suspend/{id}",
     *      operationId="suspendServiceById",
     *      tags={"Services"},
     *      summary="suspend service",
     *      description="Returns service data and result",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="service id or uuid",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="notify",
     *                     type="boolean"
     *                 ),
     *                 example={"reason": "unpaid", "notify": false}
     *             )
     *         )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ProvisioningService")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="service not found"
     *      ),
     * )
     */
    public function suspend(Request $request, Service $service)
    {
        $result = ServiceService::changeServiceStatus($request, $service, 'suspended');
        return response()->json(['data' => $service, 'result' => $result]);
    }

    /**
     * @OA\Put(
     *      path="/application/unsuspend/{id}",
     *      operationId="unsuspendServiceById",
     *      tags={"Services"},
     *      summary="unsuspend service",
     *      description="Returns service data and result",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="service id or uuid",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ProvisioningService")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="service not found"
     *      ),
     * )
     */
    public function unsuspend(string $id)
    {
        $item = Service::findOrFail($id);
        $result = $item->unsuspend();

        return response()->json(['data' => $item, 'result' => $result]);
    }

    /**
     * @OA\Delete(
     *      path="/application/services/{id}",
     *      operationId="deleteServiceById",
     *      tags={"Services"},
     *      summary="Delete service information",
     *      description="Delete service data",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="service id or uuid",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ProvisioningService")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="service not found"
     *      ),
     * )
     */
    public function destroy(string $id)
    {
        $item = Service::findOrFail($id);
        $item->delete();

        return response()->json($item);
    }
}
