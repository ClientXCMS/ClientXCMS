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



<div class="sm:flex block">

    @if (!empty($filters))
        <div class="mr-1 hs-dropdown relative inline-block md:[--placement:bottom-right]" data-hs-dropdown-auto-close="inside">
            <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                <svg class="flex-shrink-0 w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
                {{ __('global.filter') }}
                @if (count($checkedFilters) > 0)
                    <span class="ps-2 text-xs font-semibold text-blue-600 border-s border-gray-200 dark:border-gray-700 dark:text-blue-500">
                      {{ count($checkedFilters) }}
                    </span>
                @endif
            </button>
            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden mt-2 divide-y divide-gray-200 min-w-[12rem] z-10 bg-white shadow-md rounded-lg mt-2 dark:divide-gray-700 dark:bg-gray-800 dark:border dark:border-gray-700" aria-labelledby="filter-items">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($filters as $current => $label)
                        @php(is_array($label) ? $filterField = $label[1] : null)
                        @php(is_array($label) ? $label = $label[0] : $label)
                        <label for="filter-{{ $current }}" class="flex py-2.5 px-3">
                            <input id="filter-{{ $current }}" data-key="{{ $filterField }}" value="{{ $current }}" type="checkbox" class="filter-checkbox shrink-0 mt-0.5 border-gray-300 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-600 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" @if (in_array($current, $checkedFilters)) checked @endif>
                            <span class="ms-3 text-sm text-gray-800 dark:text-gray-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    @if (!empty($searchFields))
    <form id="searchForm" method="GET" action="{{ request()->url() }}">
        <label for="search" class="sr-only">{{ __('global.search') }}</label>
        <div class="relative">
            <div class="block sm:flex">
                <input type="text" value="{{ $search ?? '' }}" id="search" name="q" class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600 max-w-md mt-2 sm:mt-0" placeholder="{{ __('global.search') }}">
                <select class="ml-1 py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600 max-w-md mt-2 sm:mt-0" name="field">
                    @foreach ($searchFields as $key => $translate)
                        <option value="{{ $key }}" {{ $searchField == $key ? 'selected' : '' }}>{{ $translate }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary md:ml-1 mt-2 sm:mt-0 w-full max-w-md md:w-auto ml-1 mr-1">
                    {{ __('global.search') }}
                </button>
            </div>
        </div>
    </form>
    @endif
