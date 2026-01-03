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
use App\Models\Provisioning\CancellationReason;
use App\Models\Provisioning\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancellationReasonController extends AbstractApiController
{
    protected string $model = CancellationReason::class;

    protected array $filters = ['status', 'reason'];

    protected array $sorts = ['id', 'reason', 'status', 'created_at'];

    /**
     * @OA\Get(
     *     path="/api/cancellation_reasons",
     *     summary="List all cancellation reasons",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $items = $this->queryIndex($request);

        return response()->json($items);
    }

    /**
     * @OA\Post(
     *     path="/api/cancellation_reasons",
     *     summary="Create a new cancellation reason",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'status' => 'required|in:active,hidden,unreferenced',
        ]);

        $reason = CancellationReason::create($validated);

        return response()->json($reason, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/cancellation_reasons/{id}",
     *     summary="Get a specific cancellation reason",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(CancellationReason $cancellationReason): JsonResponse
    {
        return response()->json($cancellationReason);
    }

    /**
     * @OA\Post(
     *     path="/api/cancellation_reasons/{id}",
     *     summary="Update a cancellation reason",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(Request $request, CancellationReason $cancellationReason): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:active,hidden,unreferenced',
        ]);

        $cancellationReason->update($validated);

        return response()->json($cancellationReason);
    }

    /**
     * @OA\Delete(
     *     path="/api/cancellation_reasons/{id}",
     *     summary="Delete a cancellation reason",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(CancellationReason $cancellationReason): JsonResponse
    {
        $cancellationReason->delete();

        return response()->json(['message' => 'Cancellation reason deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/cancellation_reasons_analytics",
     *     summary="Get cancellation analytics data",
     *     tags={"Cancellation Reasons"},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Get cancellation statistics
        $stats = Service::selectRaw('cancelled_reason, COUNT(*) as count')
            ->whereNotNull('cancelled_reason')
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate.' 23:59:59'])
            ->groupBy('cancelled_reason')
            ->get();

        // Get all reasons
        $reasons = CancellationReason::all()->keyBy('id');

        $distribution = [];
        $totalCancellations = 0;

        foreach ($stats as $stat) {
            $reason = $reasons->get($stat->cancelled_reason);
            $distribution[] = [
                'reason_id' => $stat->cancelled_reason,
                'reason' => $reason ? $reason->reason : 'Unknown',
                'count' => $stat->count,
            ];
            $totalCancellations += $stat->count;
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_cancellations' => $totalCancellations,
            'distribution' => $distribution,
        ]);
    }
}
