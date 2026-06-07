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

namespace App\Http\Controllers\Front\Provisioning;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

class ServiceStatusController extends Controller
{
    public function __invoke(Request $request, Service $service): JsonResponse
    {
        $user = $request->user('web');
        // Honour the subuser ACL (customer_account_accesses) so users granted
        // 'service.show' on the owner's account can poll their delegated services.
        if ($user === null || ! Service::query()->accessibleBy($user)->whereKey($service->id)->exists()) {
            abort(404); // never leak the existence of someone else's service
        }

        $now = Carbon::now();
        $expiresAt = $service->expires_at;
        $daysToRenewal = $expiresAt ? (int) ceil($expiresAt->floatDiffInDays($now, false)) : null;

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
        ]);
    }
}
