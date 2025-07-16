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

@php
    $pendingStates = [
        \App\Models\Billing\Invoice::STATUS_PENDING,
        \App\Models\Helpdesk\SupportTicket::STATUS_OPEN,
        \App\Models\Helpdesk\SupportTicket::STATUS_ANSWERED,
        \App\Models\Billing\Invoice::STATUS_DRAFT,
        \App\Models\Provisioning\Service::STATUS_PENDING,
        'unreferenced',
    ];

    $successStates = [
        \App\Models\Billing\Invoice::STATUS_PAID,
        \App\Models\Provisioning\Service::STATUS_ACTIVE,
        'approved',
        'completed',
        'yes',
    ];

    $failedStates = [
        \App\Models\Billing\Invoice::STATUS_FAILED,
        \App\Models\Provisioning\Service::STATUS_EXPIRED,
        'rejected',
        'no'
    ];

    $neutralStates = [
        \App\Models\Billing\Invoice::STATUS_REFUNDED,
        \App\Models\Helpdesk\SupportTicket::STATUS_CLOSED,
        \App\Models\Provisioning\Service::STATUS_SUSPENDED,
        \App\Models\Provisioning\Service::STATUS_CANCELLED,
        'hidden',
        'disabled',
    ];
@endphp

@if (in_array($state, $pendingStates))
    <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-500/10 dark:text-blue-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12"
                                                                                                                     x2="12"
                                                                                                                     y1="2"
                                                                                                                     y2="6"/><line
                x1="12" x2="12" y1="18" y2="22"/><line x1="4.93" x2="7.76" y1="4.93" y2="7.76"/><line x1="16.24"
                                                                                                      x2="19.07"
                                                                                                      y1="16.24"
                                                                                                      y2="19.07"/><line
                x1="2" x2="6" y1="12" y2="12"/><line x1="18" x2="22" y1="12" y2="12"/><line x1="4.93" x2="7.76" y1="19.07"
                                                                                            y2="16.24"/><line x1="16.24"
                                                                                                              x2="19.07"
                                                                                                              y1="7.76"
                                                                                                              y2="4.93"/></svg>
        {{ __('global.states.'. $state) }}
    </span>
@endif

@if (in_array($state, $successStates))
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path
                d="m9 12 2 2 4-4"/></svg>
        {{ __('global.states.'. $state) }}
    </span>
@endif

@if (in_array($state, $failedStates))
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12"
                                                                                                                       cy="12"
                                                                                                                       r="10"/><path
                d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
        {{ __('global.states.'. $state) }}
    </span>
@endif

@if (in_array($state, $neutralStates))
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs bg-gray-100 text-gray-800 rounded-full dark:bg-slate-500/20 dark:text-slate-400">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" x2="12" y1="2" y2="12"/></svg>
        {{ __('global.states.'. $state) }}
    </span>
@endif

@if ($state == 'low')
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-500/10 dark:text-gray-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12"
                                                                                                                     x2="12"
                                                                                                                     y1="5"
                                                                                                                     y2="19"/><polyline
                points="19 12 12 19 5 12"/></svg>
        {{ __('helpdesk.priorities.'. $state) }}
    </span>
@endif

@if ($state == 'medium')
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="19" x2="12" y2="5"/>
            <polyline points="5 12 12 5 19 12"/>
        </svg>
        {{ __('helpdesk.priorities.'. $state) }}
    </span>
@endif

@if ($state == 'high')
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
        <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="20" x2="12" y2="10"/>
            <polyline points="5 15 12 8 19 15"/>
            <line x1="12" y1="14" x2="12" y2="4"/>
            <polyline points="5 9 12 2 19 9"/>
        </svg>
        {{ __('helpdesk.priorities.'. $state) }}
    </span>
@endif
