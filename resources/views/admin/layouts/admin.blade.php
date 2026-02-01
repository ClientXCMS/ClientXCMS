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

<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title') - CLIENTXCMS</title>

    @vite('resources/themes/default/js/app.js')
    @vite('resources/themes/default/css/app.scss')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ Vite::asset("resources/global/favicon.png") }}">
    @yield('styles')
</head>

<body class="bg-gray-50 dark:bg-slate-900 {{ is_darkmode(true) ? 'dark' : '' }} h-full">
<header class="flex flex-wrap sm:justify-start sm:flex-nowrap z-50 w-full bg-white border-b text-sm py-2.5 sm:py-4 dark:bg-slate-900 dark:border-gray-700">
    <nav class="max-w-7xl flex basis-full items-center w-full mx-auto px-4 sm:px-6 lg:px-8" aria-label="Global">

  <a
    href="https://clientxcms.com/client/support"
    aria-label="CLIENTXCMS"
    class="flex-none text-xl font-semibold dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
  >
    CLIENTXCMS
    <span class="bg-gray-100 text-xs text-gray-500 font-semibold rounded-full py-1 px-2 dark:bg-gray-700 dark:text-gray-400 hs-tooltip-toggle hs-tooltip inline-block relative">
      v{{ ctx_version() }}
        @if ($appIsGit)
    <span
      class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible
             opacity-0 invisible transition duration-200 z-20
             absolute start-1/2 -translate-x-1/2 mt-2
             bg-gray-900 text-white text-xs rounded px-2 py-1
             dark:bg-gray-800"
      role="tooltip"
    >
      {{ $appVersion }}
    </span>
  @endif
    </span>
  </a>
        <div class="w-full flex items-center justify-end ms-auto sm:justify-between sm:gap-x-3 sm:order-3">
            <div class="w-full flex items-center justify-end ms-auto sm:justify-between sm:gap-x-3 sm:order-3">
                <div class="sm:hidden">
                    <button id="mobileSearchButton" type="button" class="w-[2.375rem] h-[2.375rem] inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </button>
                </div>
                <div id="mobileSearchContainer" class="hidden sm:hidden w-full p-2">
                    <form method="GET" action="{{ route('admin.customers.index') }}" autocomplete="off">
                        <label for="searchBar" class="sr-only">{{ __('global.search') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                                <svg class="flex-shrink-0 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.3-4.3"/>
                                </svg>
                            </div>
                            <input type="text" id="searchBar" autocomplete="do-not-autofill" name="q" class="searchBar py-2 pe-4 ps-10 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="{{ __('global.search')  }}">

                            <div class="absolute z-50 w-full bg-white rounded-xl shadow-[0_10px_40px_10px_rgba(0,0,0,0.08)] dark:bg-slate-800" style="display: none;" data-hs-combo-box-output="">
                                <div id="dropdownItems" class="max-h-[300px] p-2 rounded-b-xl overflow-y-auto overflow-hidden [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500 dark:bg-slate-800"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="hidden mx-auto sm:block">
                    <form method="GET" action="{{ route('admin.customers.index') }}" autocomplete="off">
                        <label for="searchBar" class="sr-only">{{ __('global.search') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                                <svg class="flex-shrink-0 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.3-4.3"/>
                                </svg>
                            </div>
                            <input type="text" id="searchBarDesktop" autocomplete="do-not-autofill" name="q" class="search Barpy-2 pe-4 ps-10 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="{{ __('global.search')  }}">
                            <div class="absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-4">
                                <span class="text-gray-500">Ctrl + /</span>
                            </div>
                            <div class="absolute z-50 w-full bg-white rounded-xl shadow-[0_10px_40px_10px_rgba(0,0,0,0.08)] dark:bg-slate-800" style="display: none;" data-hs-combo-box-output="">
                                <div id="dropdownItemsDesktop" class="max-h-[300px] p-2 rounded-b-xl overflow-y-auto overflow-hidden [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500 dark:bg-slate-800"></div>
                            </div>
                        </div>
                    </form>
                </div>


            <div class="flex flex-row items-center justify-end gap-2 searchIcons">

                <button id="dark-mode-btn"  data-url="{{ route('admin.darkmode.switch') }}" class="hs-dropdown-toggle w-[2.375rem] h-[2.375rem] inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                    <svg class="@if (!is_darkmode()) hidden @endif flex-shrink-0 w-4 h-4" id="dark-mode-sun" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
                    <svg class="@if (is_darkmode()) hidden @endif flex-shrink-0 w-4 h-4" id="dark-mode-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>


                <div class="searchIcons hs-dropdown relative inline-flex" data-hs-dropdown-options='{"placement": "bottom-end"}'>
                    <button
                        class="hs-dropdown-toggle w-9 h-9 relative inline-flex justify-center items-center text-sm font-semibold rounded-lg text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                        <img class="w-5 h-5" src="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]["flag"] }}"
                             alt="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]['name'] }}">
                    </button>

                    <div
                        class="searchIcons hs-dropdown-menu min-w-[10rem] z-10 bg-white shadow-md rounded-lg p-2 dark:bg-gray-800 dark:border dark:border-gray-700 hidden"
                        aria-labelledby="hs-dropdown-with-header">
                        @foreach(\App\Services\Core\LocaleService::getLocales() as $locale => $language)
                            <a href="{{ route('locale', ['locale' => $locale]) }}"
                               class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                <img class="w-5 h-5" src="{{ $language['flag'] }}" alt="{{ $language['name'] }}">
                                {{ $language['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="hs-dropdown relative inline-flex searchIcons" data-hs-dropdown-placement="bottom-right">
                    <button id="hs-dropdown-with-header" type="button" class="w-[2.375rem] h-[2.375rem] inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-white hover:bg-white/20 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600">
                        <span class="inline-flex items-center justify-center h-[2.375rem] w-[2.375rem] rounded-full bg-gray-500 font-semibold text-white leading-none">
  {{ auth('admin')->user()->firstname[0] . auth('admin')->user()->lastname[0] }}
</span>
                    </button>

                    <div class="hs-dropdown-menu z-50 transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[15rem] bg-white shadow-md rounded-lg p-2 dark:bg-gray-800 dark:border dark:border-gray-700" aria-labelledby="hs-dropdown-with-header">
                        <div class="py-3 px-5 -m-2 bg-gray-100 rounded-t-lg dark:bg-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('auth.signed_in_as') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ auth('admin')->user()->email }}</p>
                        </div>
                        <div class="mt-2 py-2 first:pt-0 last:pb-0">

                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" >
                                <i class="bi bi-app-indicator flex-shrink-0 w-4 h-4"></i>
                                {{ __('admin.backtosite') }}
                            </a>

                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('admin.staffs.profile') }}">
                                <i class="bi bi-person-badge flex-shrink-0 w-4 h-4"></i>
                                {{ __('client.profile.index') }}
                            </a>
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('front.store.index') }}" >
                                <i class="bi bi-shop-window flex-shrink-0 w-4 h-4"></i>
                                {{ __('admin.backtostore') }}
                            </a>
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-red-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="#" id="logout-btn">
                                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5-5-5M19.8 12H9"/></svg>
                                {{ __('client.logout') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
<!-- ========== END HEADER ========== -->

<!-- ========== MAIN CONTENT ========== -->
<main id="content" role="main" class="h-screen">
    <!-- Nav -->
    <nav class="-top-px bg-white text-sm font-medium text-black ring-1 ring-gray-900 ring-opacity-5 border-t shadow-sm shadow-gray-100 pt-6 md:pb-6 -mt-px dark:bg-slate-900 dark:border-gray-800 dark:shadow-slate-700/[.7]" aria-label="Jump links">
        <div class="max-w-7xl snap-x w-full flex items-center overflow-x-auto px-4 sm:px-6 lg:px-8 pb-4 md:pb-0 mx-auto [&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500 dark:bg-slate-900">

            @foreach (app('extension')->getAdminMenuItems() as $item)
                @if (staff_has_permission($item->permission))
                    @include('admin.layouts.menu.menu', ['menuItem' => $item])
                @endif
            @endforeach
        </div>
    </nav>

    <div class="w-full pt-10 dark:bg-gray-900 dark:border-gray-700 dark:shadow-slate-700/[.7]">
        <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">
    @yield('content')
    </div>
    </div>

<form method="POST" action="{{ route('admin.logout') }}" id="logout-form">
    @csrf
</form>
@yield('scripts')
<script>
    window.admin_config = {
        intelligent_search_url: "{{ route('admin.intelligent_search') }}",
        doyouwantreally: "{{ __('admin.doyouwantreally') }}",
        cancel: "{{ __('global.cancel') }}",
        delete: "{{ __('global.delete') }}",
    }
    </script>
<script src="{{ Vite::asset('resources/themes/default/js/admin.js') }}" type="module"></script>
</main>
</body>
</html>
