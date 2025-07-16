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
namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * @OA\Get(
     *      path="/customer/me",
     *      operationId="customerMe",
     *      summary="Display the current customer details",
     *
     *      @OA\Response(
     *          response=403,
     *          description="Invalid token",
     *      ),
     *      tags={"Client API"},
     *      @OA\Response(
     *          response=200,
     *          description="Display the current customer details",
     *
     *          @OA\JsonContent(ref="#/components/schemas/Customer")
     *       ),
     *     )
     */
    public function me(Request $request)
    {
        return $request->user();
    }
}
