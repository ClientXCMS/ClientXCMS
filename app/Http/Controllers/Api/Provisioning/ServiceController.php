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
namespace App\Http\Controllers\Api\Provisioning;

use App\Http\Resources\Provisioning\ServiceCollection;
use App\Models\Provisioning\Service;
use Illuminate\Http\Request;

class ServiceController
{
    /**
     * @OA\Get(
     *      path="/application/services",
     *      operationId="getServicesList",
     *      tags={"Services"},
     *      summary="Get list of service",
     *      description="Returns list of service",
     *
     *       @OA\Response(
     *          response=200,
     *          description="Show pricings list",
     *       ),
     *
     *     @OA\Parameter(
     *         description="Number of pricings per page",
     *         in="query",
     *         name="per_page",
     *
     *         @OA\Schema(type="number", format="int64", default=12),
     *     ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      )
     *     )
     */
    public function index(Request $request)
    {
        return new ServiceCollection(Service::paginate($request->get('per_page', 12)));
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
     *          description="service id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
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
    public function show(string $id)
    {
        return Service::findOrFail($id);
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
     *          description="service id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
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
    public function expire(Request $request, string $id)
    {
        $item = Service::findOrFail($id);
        $result = $item->expire($request->get('force', 'false'));

        return response()->json(['data' => $item, 'result' => $result]);
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
     *          description="service id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
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
    public function suspend(Request $request, string $id)
    {
        $item = Service::findOrFail($id);
        $result = $item->suspend($request->get('reason'), $request->get('notify', false));

        return response()->json(['data' => $item, 'result' => $result]);
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
     *          description="service id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
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
     *          description="service id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
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
