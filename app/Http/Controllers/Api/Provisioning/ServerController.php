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
use App\Http\Requests\Provisioning\StoreServerRequest;
use App\Http\Requests\Provisioning\UpdateServerRequest;
use App\Models\Provisioning\Server;
use DB;
use Illuminate\Http\Request;

class ServerController extends AbstractApiController
{
    protected string $model = Server::class;

    protected array $sorts = [
        'id',
        'name',
        'address',
        'hostname',
        'port',
        'type',
        'status',
        'maxaccounts',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'services',
        'metadata',
    ];

    protected array $filters = [
        'id',
        'name',
        'address',
        'hostname',
        'type',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/servers",
     *     summary="Get a list of servers",
     *     tags={"Servers"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A list of servers",
     *
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ProvisioningServer"))
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
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         description="Filter by server type (pterodactyl, plesk, etc.)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Post(
     *     path="/application/servers",
     *     summary="Create a new server",
     *     tags={"Servers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "address", "type"},
     *
     *             @OA\Property(property="name", type="string", example="Node-Paris-01"),
     *             @OA\Property(property="hostname", type="string", example="paris01.example.com"),
     *             @OA\Property(property="address", type="string", example="192.168.0.10"),
     *             @OA\Property(property="port", type="integer", example=443),
     *             @OA\Property(property="username", type="string", nullable=true),
     *             @OA\Property(property="password", type="string", nullable=true),
     *             @OA\Property(property="type", type="string", example="pterodactyl"),
     *             @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}, example="active"),
     *             @OA\Property(property="maxaccounts", type="integer", nullable=true, example=100)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Server created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProvisioningServer")
     *     )
     * )
     */
    public function store(StoreServerRequest $request)
    {
        $data = $request->only(['name', 'address', 'port', 'type', 'username', 'password', 'hostname', 'maxaccounts', 'status']);
        $server = new Server;
        $server->fill($data);
        $server->save();

        return response()->json($server, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/servers/{server}",
     *     summary="Get a single server",
     *     tags={"Servers"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A single server",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProvisioningServer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="server",
     *         in="path",
     *         description="ID of the server",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function show(int $server)
    {
        return $this->queryShow($server);
    }

    /**
     * @OA\Put(
     *     path="/application/servers/{server}",
     *     summary="Update an existing server",
     *     tags={"Servers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="hostname", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="port", type="integer"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="maxaccounts", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Server updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProvisioningServer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="server",
     *         in="path",
     *         description="ID of the server",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function update(UpdateServerRequest $request, Server $server)
    {
        $data = $request->validated();
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });
        $server->fill($data);
        $server->save();

        return response()->json($server, 200);
    }

    /**
     * @OA\Delete(
     *     path="/application/servers/{server}",
     *     summary="Delete an existing server",
     *     tags={"Servers"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Server deleted successfully"
     *     ),
     *
     *     @OA\Parameter(
     *         name="server",
     *         in="path",
     *         description="ID of the server",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function destroy(Server $server)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $server->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json(['message' => 'Server deleted successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/servers/test",
     *     summary="Test server connection",
     *     tags={"Servers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"address", "type"},
     *
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="port", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="hostname", type="string"),
     *             @OA\Property(property="server_id", type="integer", description="Existing server ID to use credentials from")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Connection test result"
     *     )
     * )
     */
    public function test(Request $request)
    {
        $data = $request->only(['address', 'port', 'type', 'username', 'password', 'hostname']);
        $copy = new Server;

        if ($request->has('server_id')) {
            $server = Server::find($request->server_id);
            if ($server == null) {
                return response()->json(['success' => false, 'message' => 'Server not found'], 422);
            }
            if (empty($data['password'])) {
                $data['password'] = $server->password;
            }
            if (empty($data['username'])) {
                $data['username'] = $server->username;
            }
            if (empty($data['address'])) {
                $data['address'] = $server->address;
            }
            if (empty($data['port'])) {
                $data['port'] = $server->port;
            }
            if (empty($data['hostname'])) {
                $data['hostname'] = $server->hostname;
            }
        }

        $copy->fill($data);
        $type = $data['type'] ?? 'none';
        $serverType = app('extension')->getProductTypes()->merge(['none' => new \App\Core\NoneProductType])->filter(function ($k) use ($type) {
            return $k->server() != null && $k->server()->uuid() == $type;
        })->first();

        if ($serverType == null) {
            return response()->json(['success' => false, 'message' => 'Server type not found']);
        }

        try {
            $validator = \Validator::make($data, $serverType->server()->validate());
            if ($validator->fails()) {
                $errors = collect($validator->errors())->map(function ($item) {
                    return $item[0];
                })->implode(', ');

                return response()->json(['success' => false, 'status' => 500, 'message' => $errors]);
            }

            $result = $serverType->server()->testConnection($copy->toArray());
            if ($result->successful()) {
                return response()->json(['success' => true, 'status' => $result->status(), 'message' => $result->toString()]);
            }

            return response()->json(['success' => false, 'status' => $result->status(), 'message' => $result->toString()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
