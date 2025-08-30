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

@php($locales = \App\Services\Core\LocaleService::getLocales(true, true))
@foreach ($keys as $key => $value)
    <div id="translations-overlay-{{ $key }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#translations-overlay-{{ $key }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.settings') }}" class="p-4">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-2">
                    {{ $locale['name'] }}
                </h2>
                @if ($value == 'text')
                    @include('admin/shared/input', ['name' => "translations[". $_key . "][". $key ."]", 'value' => translated_setting($key, '', $_key)])
                @elseif ($value == 'editor')
                    @include('admin/shared/editor', ['name' => "translations[". $_key . "][". $key ."]", 'value' => translated_setting($key, '', $_key)])
                @elseif ($value == 'textarea')
                    @include('admin/shared/textarea', ['name' => "translations[". $_key . "][". $key ."]", 'value' => translated_setting($key, '', $_key)])
                @endif
            @endforeach
            <button class="btn btn-primary mt-3">{{ __('global.save') }}</button>
        </form>
    </div>
@endforeach
