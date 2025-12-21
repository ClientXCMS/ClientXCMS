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
namespace App\Http\Controllers\Api\Helpdesk;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Helpdesk\StoreDepartmentRequest;
use App\Http\Requests\Helpdesk\UpdateDepartmentRequest;
use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Http\Request;

class DepartmentController extends AbstractApiController
{
    protected string $model = SupportDepartment::class;

    protected array $sorts = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'tickets',
    ];

    protected array $filters = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/departments",
     *     summary="Get a list of support departments",
     *     tags={"Departments"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of support departments",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/SupportDepartment"))
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
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter departments",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *         @OA\Schema(type="string", default="tickets")
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Post(
     *     path="/application/departments",
     *     summary="Create a new support department",
     *     tags={"Departments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "description", "icon"},
     *             @OA\Property(property="name", type="string", example="Technical Support"),
     *             @OA\Property(property="description", type="string", example="Handles all technical issues"),
     *             @OA\Property(property="icon", type="string", example="bi bi-question-circle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Department created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportDepartment")
     *     )
     * )
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = SupportDepartment::create($request->validated());
        return response()->json($department, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/departments/{department}",
     *     summary="Get a single support department",
     *     tags={"Departments"},
     *     @OA\Response(
     *         response=200,
     *         description="A single support department",
     *         @OA\JsonContent(ref="#/components/schemas/SupportDepartment")
     *     ),
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         description="ID of the department",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function show(int $department)
    {
        return $this->queryShow($department);
    }

    /**
     * @OA\Put(
     *     path="/application/departments/{department}",
     *     summary="Update an existing support department",
     *     tags={"Departments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "description", "icon"},
     *             @OA\Property(property="name", type="string", example="Technical Support"),
     *             @OA\Property(property="description", type="string", example="Handles all technical issues"),
     *             @OA\Property(property="icon", type="string", example="bi bi-question-circle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportDepartment")
     *     ),
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         description="ID of the department",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function update(UpdateDepartmentRequest $request, SupportDepartment $department)
    {
        $department->update($request->validated());
        return response()->json($department, 200);
    }

    /**
     * @OA\Delete(
     *     path="/application/departments/{department}",
     *     summary="Delete an existing support department",
     *     tags={"Departments"},
     *     @OA\Response(
     *         response=204,
     *         description="Department deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Department cannot be deleted (has tickets)"
     *     ),
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         description="ID of the department",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function destroy(SupportDepartment $department)
    {
        if ($department->tickets()->count() > 0) {
            return response()->json(['message' => __('helpdesk.admin.departments.error_delete')], 403);
        }
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully'], 204);
    }
}
