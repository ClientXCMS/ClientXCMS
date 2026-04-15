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

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Api\AbstractApiController;
use App\Models\ActionLog;
use Illuminate\Http\Request;

class ActionLogController extends AbstractApiController
{
    protected string $model = ActionLog::class;

    protected int $perPage = 50;

    protected array $sorts = [
        'id',
        'action',
        'model',
        'model_id',
        'staff_id',
        'customer_id',
        'created_at',
    ];

    protected array $relations = [
        'customer',
        'staff',
        'entries',
    ];

    protected array $filters = [
        'id',
        'action',
        'model',
        'model_id',
        'staff_id',
        'customer_id',
        'created_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/logs",
     *     summary="Get a list of action logs",
     *     tags={"Action Logs"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A list of action logs"
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order (default: -created_at)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[action]",
     *         in="query",
     *         description="Filter by action type",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[model]",
     *         in="query",
     *         description="Filter by model name",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[staff_id]",
     *         in="query",
     *         description="Filter by staff ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[customer_id]",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="customer,staff,entries")
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Get(
     *     path="/application/logs/{log}",
     *     summary="Get a single action log",
     *     tags={"Action Logs"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A single action log with details"
     *     ),
     *
     *     @OA\Parameter(
     *         name="log",
     *         in="path",
     *         description="ID of the action log",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function show(int $log)
    {
        return $this->queryShow($log);
    }
}
