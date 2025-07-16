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
?>
?>
?>
@props(['expires_at' => null, 'state' => 'active', 'date_at' => null])

@php
    $days = $expires_at != null ? \Carbon\Carbon::parse($expires_at)->diffInDays() : null;
    $inFuture = $expires_at != null ? \Carbon\Carbon::parse($expires_at)->isFuture() : false;
    if ($inFuture == false) {
        $days = null;
    }
@endphp

@if ($days == null && $expires_at == null)
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-info-emphasis bg-info-subtle border border-info-subtle rounded-2">
        <i class="bi bi-clock-fill me-1"></i>
            {{ __('recurring.onetime') }}
    </small>
@endif

@if (is_null($days) && $expires_at != null)
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-danger-emphasis bg-danger-subtle border border-danger-subtle rounded-2">
        <i class="bi bi-exclamation-circle-fill me-1"></i>
       {{ __('client.services.expired_at', ['date' => \Carbon\Carbon::parse($date_at)->format('d/m/y')]) }}
    </small>
@endif

@if ($days >= 7)
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-success-emphasis bg-success-subtle border border-success-subtle rounded-2">
        <i class="bi bi-check-circle-fill me-1"></i>
            {{ __('client.services.daysremaining', ['days' => $days]) }}
    </small>
@endif

@if ($days <= 3 && is_int($days))
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-danger-emphasis bg-danger-subtle border border-danger-subtle rounded-2">
        <i class="bi bi-alarm-fill me-1"></i>
        @if ($days < 1)
            {{ __('client.services.dayremaining', ['days' => $days]) }}
        @else
            {{ __('client.services.daysremaining', ['days' => $days]) }}
        @endif
    </small>
@endif

@if ($days < 7 && $days > 3)
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-warning-emphasis bg-warning-subtle border border-warning-subtle rounded-2">
        <i class="bi bi-hourglass-split me-1"></i>
            {{ __('client.services.daysremaining', ['days' => $days]) }}
    </small>
@endif
