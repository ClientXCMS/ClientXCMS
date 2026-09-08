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


<button type="button" id="mass_action_btn" class="hidden" data-hs-overlay="#mass_action_overlay">
</button>
<div id="mass_action_overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-neutral-800 dark:border-neutral-700" tabindex="-1">
    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-neutral-700">
        <h3 class="font-bold text-gray-800 dark:text-white" id="mass_action_overlay_title">
            {{ __('global.mass_actions') }}
        </h3>
        <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-neutral-700" data-hs-overlay="#mass_action_overlay">
            <span class="sr-only">{{ __('global.closemodal') }}</span>
            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>
    </div>
    <div class="p-4">
        <form id="mass_action_form" action="{{ route($routePath  . '.mass_action') }}" method="POST">
            @csrf
            @include('admin/shared/input', ['name' => 'input', 'type' => 'hidden', 'value' => '', 'label' => '', 'attributes' => ['id' => 'mass_action_input']])
            <input type="hidden" name="ids" id="mass_action_ids">
            <input type="hidden" name="action" id="mass_action_action">

            <ul class="marker:text-blue-600 list-disc ps-5 space-y-2 text-sm text-gray-600 dark:text-neutral-400 mt-2" id="mass_actions_list">
            </ul>
            <button type="submit" class="btn btn-primary w-full mt-2">
                {{ __('global.validate') }}
            </button>
        </form>
    </div>
</div>
