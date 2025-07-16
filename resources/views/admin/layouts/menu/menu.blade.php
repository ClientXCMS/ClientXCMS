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
@if (empty($menuItem->children))
    <div class="snap-center shrink-0 pe-5 sm:pe-8 sm:last-pe-0">
        <a href="{{ route($menuItem->route) }}" class="{{ is_subroute(route($menuItem->route, [], false)) ? 'text-indigo-600 dark:text-indigo-600 hover:text-indigo-600 dark:hover:text-indigo-600' : '' }} inline-flex items-center gap-x-2 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-500 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
            <i class="{{ $menuItem->icon }} flex-shrink-0" style="font-size: 16px;"></i>
            {{ __($menuItem->translation) }}
        </a>
    </div>
@else
<li class="hs-accordion" id="{{ $menuItem->uuid }}-accordion">
        <button type="button" class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2 px-2.5 hs-accordion-active:text-blue-600 hs-accordion-active:hover:bg-transparent text-sm text-slate-700 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-900 dark:text-slate-400 dark:hover:text-slate-300 dark:hs-accordion-active:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
            <i class="{{ $menuItem->icon }} flex-shrink-0 w-4 h-4"></i>
            {{ __($menuItem->translation) }}
            <svg class="hs-accordion-active:block ms-auto hidden w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m18 15-6-6-6 6"/>
            </svg>
            <svg class="hs-accordion-active:hidden ms-auto block w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>

        <div id="{{ $menuItem->uuid }}-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 hidden">
            <ul class="hs-accordion-group ps-3 pt-2" data-hs-accordion-always-open>
                @include('admin.layouts.menu.children', ['children' => $menuItem->children])
            </ul>
        </div>
    </li>
@endif
