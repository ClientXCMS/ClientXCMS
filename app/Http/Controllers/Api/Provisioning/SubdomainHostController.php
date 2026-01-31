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
use App\Models\Provisioning\SubdomainHost;
use Illuminate\Http\Request;

class SubdomainHostController extends AbstractApiController
{
    protected string $model = SubdomainHost::class;

    protected array $sorts = [
        'id',
        'domain',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [];

    protected array $filters = [
        'id',
        'domain',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/subdomains",
     *     summary="Get a list of subdomain hosts",
     *     tags={"Subdomain Hosts"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A list of subdomain hosts"
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
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Post(
     *     path="/application/subdomains",
     *     summary="Create a new subdomain host",
     *     tags={"Subdomain Hosts"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"domain"},
     *
     *             @OA\Property(property="domain", type="string", example=".example.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Subdomain host created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:subdomains_hosts,domain',
        ]);

        $subdomain = SubdomainHost::create($validated);

        return response()->json($subdomain, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/subdomains/{subdomain}",
     *     summary="Get a single subdomain host",
     *     tags={"Subdomain Hosts"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="A single subdomain host"
     *     ),
     *
     *     @OA\Parameter(
     *         name="subdomain",
     *         in="path",
     *         description="ID of the subdomain host",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function show(int $subdomain)
    {
        return $this->queryShow($subdomain);
    }

    /**
     * @OA\Put(
     *     path="/application/subdomains/{subdomain}",
     *     summary="Update an existing subdomain host",
     *     tags={"Subdomain Hosts"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"domain"},
     *
     *             @OA\Property(property="domain", type="string", example=".newdomain.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Subdomain host updated successfully"
     *     ),
     *
     *     @OA\Parameter(
     *         name="subdomain",
     *         in="path",
     *         description="ID of the subdomain host",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function update(Request $request, SubdomainHost $subdomain)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:subdomains_hosts,domain,'.$subdomain->id,
        ]);

        $subdomain->update($validated);

        return response()->json($subdomain, 200);
    }

    /**
     * @OA\Delete(
     *     path="/application/subdomains/{subdomain}",
     *     summary="Delete an existing subdomain host",
     *     tags={"Subdomain Hosts"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Subdomain host deleted successfully"
     *     ),
     *
     *     @OA\Parameter(
     *         name="subdomain",
     *         in="path",
     *         description="ID of the subdomain host",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function destroy(SubdomainHost $subdomain)
    {
        $subdomain->delete();

        return response()->json(['message' => 'Subdomain host deleted successfully'], 200);
    }
}
