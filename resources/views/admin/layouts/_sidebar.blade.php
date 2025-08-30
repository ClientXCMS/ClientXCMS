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
<!-- Sidebar -->
<div class="flex">
    <!-- Backdrop (pour mobile, contrôlé par AlpineJS) -->
    <div :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black opacity-50 transition-opacity lg:hidden"></div>

    <!-- Contenu de la Sidebar -->
    <div :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed inset-y-0 left-0 z-30 w-64 flex flex-col transition duration-300 transform bg-white dark:bg-gray-800 lg:translate-x-0 lg:static lg:inset-0">

        <!-- ===== TÊTE DE LA SIDEBAR (NON SCROLLABLE) ===== -->
        <div>
            <!-- Logo -->
            <div class="px-6 pt-6 flex justify-center">
                <a class="flex-none text-xl font-semibold dark:text-white" href="{{ route('admin.dashboard') }}" aria-label="CLIENTXCMS">
                    CLIENTXCMS
                    <span class="bg-gray-100 text-xs text-gray-500 font-semibold rounded-full py-1 px-2 dark:bg-gray-700 dark:text-gray-400">v{{ ctx_version() }}</span>
                </a>
            </div>

            <!-- Bloc Profil Utilisateur -->
            <div class="px-6 py-4 mt-4 border-y dark:border-gray-700">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ auth('admin')->user()->firstname }} {{ auth('admin')->user()->lastname }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ auth('admin')->user()->email }}</p>
                <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1 font-semibold uppercase">
                    {{ optional(auth('admin')->user()->role)->name }}
                </p>
            </div>
        </div>

        <!-- ===== CORPS DE LA SIDEBAR (NAVIGATION SCROLLABLE) ===== -->
        <!-- La classe `flex-1` fait en sorte que cette section prenne tout l'espace vertical restant -->
        <!-- `overflow-y-auto` ajoute une barre de défilement si le menu est trop long -->
        <nav class="flex-1 mt-2 px-4 overflow-y-auto">

            {{-- Boucle pour les éléments de menu principaux --}}
            @foreach (app('extension')->getAdminMenuItems() as $item)
                @if (staff_has_permission($item->permission))
                    @include('admin.layouts.vertical-menu', ['menuItem' => $item])
                @endif
            @endforeach

            {{-- Boucle pour les cartes de paramètres --}}
            @foreach(app('settings')->getCards() as $card)
                @if(collect($card->items)->some(fn($item) => $item->isActive()))
                    @php $isCardActive = request('card') == $card->uuid; @endphp
                    <div x-data="{ open: {{ $isCardActive ? 'true' : 'false' }} }" class="mt-4">
                        <button @click="open = !open" class="flex items-center justify-between w-full py-2 px-2 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none rounded-lg">
                            <span class="flex items-center">
                                <i class="bi bi-archive w-5 h-5"></i>
                                <span class="mx-3">{{ __($card->name) }}</span>
                            </span>
                            <svg class="h-4 w-4 transform transition-transform" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div x-show="open" class="pl-8 pt-2 transition-all duration-300">
                            @foreach ($card->items as $item)
                                @if($item->isActive())
                                    @php $isItemActive = request('uuid') == $item->uuid; @endphp
                                    <a href="{{ $item->url() }}" class="flex items-center mt-1 py-2 px-4 text-sm rounded-lg {{ $isItemActive ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400' }} hover:text-indigo-600 dark:hover:text-indigo-400">
                                        <i class="{{ $item->icon }} w-5 h-5 mr-3"></i>
                                        <span>{{ __($item->name) }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>
</div>
