<?php

/*
 * This file is part of the CLIENTXCMS project.
 * This is the Client API ServiceController.
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Customer Services",
 *     description="Service endpoints for customer API"
 * )
 */
class ServiceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client/services",
     *     summary="List customer's services",
     *     tags={"Customer Services"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (active, suspended, cancelled, expired, pending)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of services"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::where('customer_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $services->map(fn ($service) => $this->formatService($service)),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/services/{service}",
     *     summary="Get service details",
     *     tags={"Customer Services"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service details"
     *     ),
     *     @OA\Response(response=404, description="Service not found")
     * )
     */
    public function show(Request $request, Service $service): JsonResponse
    {
        if ($service->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('provisioning.services.not_found')], 404);
        }

        return response()->json([
            'data' => $this->formatService($service, true),
        ]);
    }

    private function formatService(Service $service, bool $detailed = false): array
    {
        $data = [
            'id' => $service->id,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'status_label' => __('provisioning.services.status.'.$service->status),
            'currency' => $service->currency,
            'billing_cycle' => $service->billing,
            'price_ht' => $service->getBillingPrice()->priceHT(),
            'price_ttc' => $service->getBillingPrice()->priceTTC(),
            'expires_at' => $service->expires_at?->toIso8601String(),
            'suspended_at' => $service->suspended_at?->toIso8601String(),
            'cancelled_at' => $service->cancelled_at?->toIso8601String(),
            'created_at' => $service->created_at->toIso8601String(),
            'can_renew' => $service->canRenew(),
        ];

        if ($service->product) {
            $data['product'] = [
                'id' => $service->product->id,
                'name' => $service->product->name,
                'type' => $service->product->type,
            ];
        }

        if ($service->server) {
            $data['server'] = [
                'id' => $service->server->id,
                'name' => $service->server->name,
            ];
        }

        if ($detailed) {
            // Add service-specific data
            $data['data'] = $service->data ?? [];

            // Add renewal info
            if ($service->canRenew()) {
                $data['renewal'] = [
                    'next_due_date' => $service->expires_at?->toIso8601String(),
                    'days_until_expiry' => $service->expires_at ? now()->diffInDays($service->expires_at, false) : null,
                ];
            }

            // Add suspension info if suspended
            if ($service->isSuspended()) {
                $data['suspension'] = [
                    'reason' => $service->suspend_reason,
                    'suspended_at' => $service->suspended_at?->toIso8601String(),
                ];
            }

            // Add cancellation info if pending cancellation
            if ($service->cancelled_reason !== null) {
                $data['cancellation'] = [
                    'reason' => $service->cancelled_reason,
                    'scheduled_at' => $service->cancelled_at?->toIso8601String(),
                ];
            }
        }

        return $data;
    }
}
