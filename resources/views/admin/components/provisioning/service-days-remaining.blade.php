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
?>
@props(['expires_at' => null, 'state' => 'active'])
@php
    $days = $expires_at != null ? abs((int)\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $expires_at)->diffInDays()) : null;
    $inFuture = $expires_at != null ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $expires_at)->isFuture() : false;
    if ($inFuture == false) {
        $days = null;
    }
@endphp
@if ($days == null && $expires_at == null)
    <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-500/10 dark:text-blue-500">
          <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                  d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path
                  d="m9 12 2 2 4-4"/></svg>
          {{ __('recurring.onetime') }}
</span>
@endif
@if (gettype($days) == "NULL" && $expires_at != null)
    <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                  <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                       fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12"
                                                                                                                                 cy="12"
                                                                                                                                 r="10"/><path
                          d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                            {{ __('client.services.expired_at', ['date' => \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $expires_at)->format('d/m/y')]) }}


</span>
@endif
@if ($days >= 7)
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
         <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                 d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path
                 d="m9 12 2 2 4-4"/></svg>
                            {{ __('client.services.daysremaining', ['days' => $days]) }}

</span>
@endif

@if ($days <= 3 && gettype($days) == "integer")
    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">

<i class="bi bi-alarm"></i>
        @if ($days < 1)
            {{ __('client.services.dayremaining', ['days' => $days]) }}
        @else
            {{ __('client.services.daysremaining', ['days' => $days]) }}
        @endif
</span>
@endif
@if ($days < 7 && $days > 3)


    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs bg-gray-100 text-gray-800 rounded-full dark:bg-slate-500/20 dark:text-slate-400">
<svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
        d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path
        d="m9 12 2 2 4-4"/></svg>
                    {{ __('client.services.daysremaining', ['days' => $days]) }}

</span>
@endif
