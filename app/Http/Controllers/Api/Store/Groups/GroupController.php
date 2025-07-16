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
namespace App\Http\Controllers\Api\Store\Groups;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Store\StoreGroupRequest;
use App\Http\Requests\Store\UpdateGroupRequest;
use App\Http\Resources\Store\GroupCollection;
use App\Models\Store\Group;
use Illuminate\Http\Request;

class GroupController extends AbstractApiController
{
    protected array $sorts = [
        'name',
        'slug',
        'status',
        'description',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'products',
        'groups',
        'metadata',
    ];

    protected array $filters = [
        'name',
        'slug',
        'status',
        'description',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected string $model = Group::class;

    /**
     * @OA\Get(
     *      path="/application/groups",
     *      operationId="getGroupList",
     *      tags={"Groups"},
     *      summary="Get list of groups",
     *      description="Returns list of groups with optional filters, sorting and relations",
     *
     *      @OA\Parameter(
     *          name="filter[name]",
     *          in="query",
     *          description="Filter by name",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="filter[slug]",
     *          in="query",
     *          description="Filter by slug",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sort by field, e.g. sort=name or sort=-name for descending",
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="include",
     *          in="query",
     *          description="Include relations (products, groups, metadata)",
     *
     *          @OA\Schema(type="string", example="products,groups")
     *      ),
     *
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of results per page",
     *
     *          @OA\Schema(type="integer", default=12)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="List of groups"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function index(Request $request)
    {
        return new GroupCollection($this->queryIndex($request));
    }

    /**
     * @OA\Post(
     *      path="/application/groups",
     *      operationId="storeGroup",
     *      tags={"Groups"},
     *      summary="Create a new group",
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/StoreGroupRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Group created successfully"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     */
    public function store(StoreGroupRequest $request)
    {
        $group = $request->store();

        return response()->json($group, 201);
    }

    /**
     * @OA\Get(
     *      path="/application/groups/{id}",
     *      operationId="getGroupById",
     *      tags={"Groups"},
     *      summary="Get group information",
     *      description="Returns group data",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
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
     *           description="Include relations (products, groups, metadata)",
     *
     *           @OA\Schema(type="string", example="products,groups")
     *       ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ShopGroup")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="group not found"
     *      ),
     * )
     */
    public function show(Group $group)
    {
        return response()->json($group);
    }

    /**
     * @OA\Post(
     *      path="/application/groups/{id}",
     *      operationId="updateGroup",
     *      tags={"Groups"},
     *      summary="Update an existing group",
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="Group ID",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/UpdateGroupRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Group updated successfully"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        $request->update();

        return response()->json($group);
    }

    /**
     * @OA\Delete(
     *      path="/application/groups/{id}",
     *      operationId="deleteGroupById",
     *      tags={"Groups"},
     *      summary="Delete group information",
     *      description="Delete group data",
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="group id",
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
     *          @OA\JsonContent(ref="#/components/schemas/ShopGroup")
     *       ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="group not found"
     *      ),
     * )
     */
    public function destroy(Group $group)
    {
        $group->groups->map(function ($group) {
            $group->update(['parent_id' => null]);
        });
        if ($group->products->isNotEmpty()) {
            return response()->json(['error' => __('global.groupcannotdeleted')], 400);
        }
        $group->delete();

        return response()->json($group);
    }
}
