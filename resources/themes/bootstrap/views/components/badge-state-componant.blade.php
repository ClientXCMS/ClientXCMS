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
@props(['state' => 'pending'])

@if ($state == \App\Models\Billing\Invoice::STATUS_PENDING || $state == \App\Models\Helpdesk\SupportTicket::STATUS_OPEN  || $state == \App\Models\Helpdesk\SupportTicket::STATUS_ANSWERED || $state == \App\Models\Billing\Invoice::STATUS_DRAFT  || $state == \App\Models\Provisioning\Service::STATUS_PENDING || $state == 'unreferenced')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-primary-emphasis bg-primary-subtle border border-primary-subtle rounded-2">
        {{ __('global.states.'. $state) }}
    </small>
@endif

@if ($state == \App\Models\Billing\Invoice::STATUS_PAID || $state == \App\Models\Provisioning\Service::STATUS_ACTIVE || $state == 'approved' || $state == 'completed')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-success-emphasis bg-success-subtle border border-success-subtle rounded-2">
        {{ __('global.states.'. $state) }}
    </small>
@endif

@if ($state == \App\Models\Billing\Invoice::STATUS_FAILED  || $state == \App\Models\Provisioning\Service::STATUS_EXPIRED || $state == 'rejected')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-danger-emphasis bg-danger-subtle border border-danger-subtle rounded-2">
        {{ __('global.states.'. $state) }}
    </small>
@endif

@if ($state == \App\Models\Billing\Invoice::STATUS_REFUNDED || $state == \App\Models\Helpdesk\SupportTicket::STATUS_CLOSED || $state == \App\Models\Provisioning\Service::STATUS_SUSPENDED || $state == \App\Models\Provisioning\Service::STATUS_CANCELLED || $state == 'hidden' || $state == 'disabled')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-secondary-emphasis bg-secondary-subtle border border-secondary-subtle rounded-2">
        {{ __('global.states.'. $state) }}
    </small>
@endif

@if ($state == 'low')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-gray-emphasis bg-gray-subtle border border-gray-subtle rounded-2">
        {{ __('helpdesk.priorities.'. $state) }}
    </small>
@endif
@if ($state == 'high')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-danger-emphasis bg-danger-subtle border border-danger-subtle rounded-2">
        {{ __('helpdesk.priorities.'. $state) }}
    </small>
@endif
@if ($state == 'medium')
    <small class="d-inline-flex mb-3 px-2 py-1 fw-semibold text-warning-emphasis bg-warning-subtle border border-warning-subtle rounded-2">
        {{ __('helpdesk.priorities.'. $state) }}
    </small>
@endif
