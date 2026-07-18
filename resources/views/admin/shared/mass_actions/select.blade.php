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


<div class="py-1 px-4 flex justify-between items-center">
    <div></div>
    {{ $items->links('admin.shared.layouts.pagination') }}
    <div>
        <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('global.showing') }} {{ $items->firstItem() }} - {{ $items->lastItem() }} {{ __('global.of') }} {{ $items->total() }} {{ __('global.results') }}
        </span>
    </div>
</div>

<div id="mass-action-floating-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden">
    <div class="flex items-center gap-4 px-6 py-4 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
            <span id="mass-action-selected-count">0</span> {{ __('admin.bulk.selected') }}
        </span>
        <div class="h-6 w-px bg-gray-300 dark:bg-slate-600"></div>
        <select id="mass_action_select" class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600">
            <option value="action">{{ __('global.actions') }}</option>
            @foreach ($mass_actions as $mass_action)
                <option value="{{ $mass_action->action }}"{!! $mass_action->question ? ' data-question="' . e($mass_action->question).'"' : '' !!}>{{ $mass_action->translate }}</option>
            @endforeach
        </select>
    </div>
</div>
