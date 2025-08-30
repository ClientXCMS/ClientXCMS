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
@if (empty($menuItem->children))
    {{-- Élément de menu simple, sans sous-menu --}}
    <a href="{{ route($menuItem->route) }}" class="flex items-center mt-4 py-2 px-2 rounded-lg {{ is_subroute(route($menuItem->route, [], false)) ? 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' : 'text-gray-700 dark:text-gray-300' }} hover:bg-gray-200 dark:hover:bg-gray-700">
        <i class="{{ $menuItem->icon }} w-5 h-5"></i>
        <span class="mx-3">{{ __($menuItem->translation) }}</span>
    </a>
@else
    {{-- Élément de menu avec sous-menu (accordéon) --}}
    @php
        // Détermine si une sous-route de ce menu est active pour le garder ouvert
        $isMenuSectionActive = is_subroute(route($menuItem->route, [], false), true);
    @endphp

    {{-- 1. x-data : initialise l'état 'open' du menu --}}
    <div x-data="{ open: {{ $isMenuSectionActive ? 'true' : 'false' }} }" class="mt-4">
        {{-- 2. @click : bascule l'état 'open' --}}
        <button @click="open = !open" class="flex items-center justify-between w-full py-2 px-2 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none rounded-lg">
            <span class="flex items-center">
                <i class="{{ $menuItem->icon }} w-5 h-5"></i>
                <span class="mx-3">{{ __($menuItem->translation) }}</span>
            </span>
            <svg class="h-4 w-4 transform transition-transform" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        {{-- 3. x-show : affiche les enfants du menu si 'open' est true --}}
        <div x-show="open" class="pl-8 pt-2 transition-all duration-300">
            @foreach($menuItem->children as $child)
                {{-- Appel récursif pour afficher les enfants, qui peuvent eux-mêmes avoir des enfants --}}
                @include('admin.layouts.vertical-menu', ['menuItem' => $child])
            @endforeach
        </div>
    </div>
@endif
