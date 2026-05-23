<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Front\Provisioning;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceUsageMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

/**
 * v2.16 — Live status endpoint polled by the customer-facing service
 * page.
 *
 *   GET /client/services/{uuid}/status
 *
 * Returns a lightweight JSON snapshot:
 *   - status:          current Service::status
 *   - state:           Service::state (active, suspended, expired, ...)
 *   - expires_at:      ISO timestamp
 *   - days_to_renewal: integer (negative if past due)
 *   - last_check:      ISO timestamp of the most recent usage sample
 *   - usage_estimate:  array<{ key, label, peak, unit_price,
 *                              included_quantity, charge }>
 *     Computed only when the product has metered rates declared.
 *
 * The endpoint is signed in front of the regular `auth` middleware so
 * other customers can't poll someone else's service. A throttle keeps
 * the polling-loop cheap even with many tabs open.
 */
class ServiceStatusController extends Controller
{
    public function __invoke(Request $request, Service $service): JsonResponse
    {
        $user = $request->user('web');
        if ($user === null || (int) $service->customer_id !== (int) $user->id) {
            abort(404); // never leak the existence of someone else's service
        }

        $now = Carbon::now();
        $expiresAt = $service->expires_at;
        $daysToRenewal = $expiresAt ? (int) ceil($expiresAt->floatDiffInDays($now, false)) : null;

        $lastSample = ServiceUsageMetric::where('service_id', $service->id)
            ->latest('captured_at')
            ->value('captured_at');

        // v2.16 — server-rendered HTML fragments for the index row + show header.
        // We re-render the same Blade components the index page already uses so
        // the live updates have identical markup (no risk of CSS divergence and
        // future tweaks to <x-badge-state> flow into the live updates too).
        $statusBadgeHtml = Blade::render(
            '<x-badge-state state="{{ $state }}"></x-badge-state>',
            ['state' => $service->status]
        );

        $daysRemainingHtml = Blade::render(
            '<x-service-days-remaining expires_at="{{ $expiresAt }}" state="{{ $state }}"></x-service-days-remaining>',
            [
                'expiresAt' => $expiresAt?->toDateTimeString(),
                'state' => $service->status,
            ]
        );

        return response()->json([
            'uuid' => $service->uuid,
            'status' => $service->status,
            'state' => method_exists($service, 'state') ? $service->state : $service->status,
            'state_label' => __('global.states.' . $service->status),
            'status_badge_html' => $statusBadgeHtml,
            'days_remaining_html' => $daysRemainingHtml,
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_at_label' => $expiresAt?->isoFormat('LLL'),
            'days_to_renewal' => $daysToRenewal,
            'last_check' => $lastSample?->toIso8601String(),
            'usage_estimate' => $this->usageEstimate($service, $now),
        ]);
    }

    /**
     * Compute the estimated metered charge so far this month — gives
     * the customer a live preview of the upcoming invoice.
     *
     * @return array<int, array<string, mixed>>
     */
    private function usageEstimate(Service $service, Carbon $now): array
    {
        if (! class_exists(\App\Models\Store\ProductMeteredRate::class)) {
            return [];
        }
        if ($service->product_id === null) {
            return [];
        }

        $rates = \App\Models\Store\ProductMeteredRate::where('product_id', $service->product_id)->get();
        if ($rates->isEmpty()) {
            return [];
        }

        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        return $rates->map(function ($rate) use ($service, $start, $end) {
            $peak = (float) ServiceUsageMetric::where('service_id', $service->id)
                ->where('metric_key', $rate->metric_key)
                ->whereBetween('captured_at', [$start, $end])
                ->max('value');
            $charge = $rate->chargeFor($peak);

            return [
                'metric_key' => $rate->metric_key,
                'label' => $rate->label,
                'unit' => $rate->unit,
                'peak' => $peak,
                'included_quantity' => (float) $rate->included_quantity,
                'unit_price' => (float) $rate->unit_price,
                'charge' => $charge,
                'currency' => $rate->currency,
            ];
        })->all();
    }
}
