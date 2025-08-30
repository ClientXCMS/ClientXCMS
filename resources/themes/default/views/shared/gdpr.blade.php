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

<div id="cookie-banner" class="fixed bottom-0 start-1/2 transform -translate-x-1/2 z-60 sm:max-w-4xl w-full mx-auto p-6 opacity-80">
    <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-2xs dark:bg-gray-700 dark:border-gray-600">
        <div class="flex justify-between items-center gap-x-5 sm:gap-x-10">
            <div class="grow">
                <h2 class="text-sm text-gray-600 dark:text-neutral-400">
                    {{ __('global.gdpr.text') }}
                    <a class="inline-flex items-center gap-x-1.5 text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500" href="{{ setting('gdrp_cookies_privacy_link') }}" target="_blank">{{ __('global.gdpr.link') }}</a>.
                </h2>
            </div>
            <button  data-hs-remove-element="#cookie-banner" id="cookie-btn" data-url="{{ route('gdpr') }}" type="button" class="p-2 inline-flex items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-white/10 dark:text-white dark:hover:bg-white/20 dark:hover:text-white dark:focus:bg-white/20 dark:focus:text-white">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>
    </div>
</div>
