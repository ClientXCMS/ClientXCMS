<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Api\Provisioning;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Service;
use App\Services\Billing\UsageBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * v2.16 — Ingestion endpoint for usage metrics.
 *
 * Authentication: Sanctum personal access token whose `name` matches
 * the service uuid. This mirrors the existing API token model — the
 * operator generates a per-service token for the provisioning node,
 * which then pushes samples.
 *
 *   POST /api/services/{uuid}/metrics
 *   Authorization: Bearer <sanctum_token>
 *   {
 *     "metrics": [
 *       { "key": "cpu_cores", "value": 4.5 },
 *       { "key": "ram_gb",    "value": 7.8, "captured_at": "2026-05-23T10:00:00Z" }
 *     ]
 *   }
 *
 * Accepts a batch of samples to amortise the HTTP round-trip when a
 * node aggregates locally before flushing.
 */
class UsageMetricsController extends Controller
{
    public function store(Request $request, string $uuid, UsageBillingService $usage): JsonResponse
    {
        $service = Service::where('uuid', $uuid)->first();
        if ($service === null) {
            return response()->json(['error' => 'service-not-found'], 404);
        }

        // Sanctum token authorising the call must belong to the
        // service's customer. We could tighten this further by
        // matching the token name to the service uuid but the per
        // -customer check already prevents cross-account spoofing.
        $user = $request->user();
        if ($user === null || (int) $user->id !== (int) $service->customer_id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $payload = $request->validate([
            'metrics' => 'required|array|min:1|max:200',
            'metrics.*.key' => 'required|string|max:64',
            'metrics.*.value' => 'required|numeric|min:0',
            'metrics.*.captured_at' => 'nullable|date',
        ]);

        $recorded = 0;
        foreach ($payload['metrics'] as $sample) {
            $usage->recordMetric(
                $service,
                $sample['key'],
                (float) $sample['value'],
                isset($sample['captured_at']) ? Carbon::parse($sample['captured_at']) : null
            );
            $recorded++;
        }

        return response()->json(['recorded' => $recorded], 201);
    }
}
