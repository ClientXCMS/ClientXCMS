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


<a href="{{ route('front.store.basket.show') }}" class="btn-icon2">
    <svg class="hs-collapse-open:hidden flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
         stroke-linejoin="round">
        <circle cx="10" cy="20.5" r="1"/>
        <circle cx="18" cy="20.5" r="1"/>
        <path d="M2.5 2.5h3l2.7 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6l1.6-8.4H7.1"/>
    </svg>
    @if (basket(false) != null && basket()->quantity() > 0)
        <span
            class="absolute top-0 end-0 inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium transform -translate-y-1/2 translate-x-1/2 bg-red-500 text-white">{{ basket()->quantity() }}</span>
    @endif
    <svg class="hs-collapse-open:block hidden flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24"
         height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
         stroke-linejoin="round">
        <path d="M18 6 6 18"/>
        <path d="m6 6 12 12"/>
    </svg>
</a>
@if (setting('theme_switch_mode') == 'both')
    <button id="dark-mode-btn"  data-url="{{ route('darkmode.switch') }}"
            class="w-9 h-9 relative inline-flex justify-center items-center text-sm font-semibold rounded-lg text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
        <svg class="@if (!is_darkmode()) hidden @endif flex-shrink-0 w-4 h-4" id="dark-mode-sun"
             xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"/>
            <path
                d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>
        </svg>
        <svg class="@if (is_darkmode()) hidden @endif flex-shrink-0 w-4 h-4" id="dark-mode-moon"
             xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>
@endif
<div class="hs-dropdown relative inline-flex" data-hs-dropdown-options='{"placement": "bottom-end"}'>
    <button
        class="hs-dropdown-toggle w-9 h-9 relative inline-flex justify-center items-center text-sm font-semibold rounded-lg text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
        <img class="w-5 h-5" src="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]["flag"] }}"
             alt="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]['name'] }}">
    </button>

    <div
        class="hs-dropdown-menu min-w-[10rem] z-10 bg-white shadow-md rounded-lg p-2 dark:bg-gray-800 dark:border dark:border-gray-700 hidden"
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
@if (auth('web')->guest())

    <a href="{{ route('login') }}" class="btn-icon2">
        @include('shared.icons.user')
    </a>
@else

    <div class="hs-dropdown relative inline-flex" data-hs-dropdown-placement="bottom-right">
        <button id="hs-dropdown-with-header" type="button"
                class="hs-dropdown-toggle w-[2.375rem] h-[2.375rem] inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                        <span
                            class="inline-flex items-center justify-center h-[2.375rem] w-[2.375rem] rounded-full bg-gray-500 font-semibold text-white leading-none">
                          {{ Auth::user()->firstname[0] . Auth::user()->lastname[0] }}
                        </span>
        </button>

        <div
            class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[15rem] z-10 bg-white shadow-md rounded-lg p-2 dark:bg-gray-800 dark:border dark:border-gray-700"
            aria-labelledby="hs-dropdown-with-header">
            <div class="py-3 px-5 -m-2 bg-gray-100 rounded-t-lg dark:bg-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('auth.signed_in_as') }}</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ Auth::user()->email }}</p>
            </div>
            <div class="mt-2 py-2 first:pt-0 last:pb-0">

                @if (Session::has('autologin'))

                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                       href="{{ route('admin.customers.logout') }}">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5-5-5M19.8 12H9"/>
                        </svg>

                        {{ __('admin.customers.autologin.logout') }}
                    </a>
                @endif
                @foreach(\App\Http\Navigation\ClientNavigationMenu::getItems() as $item)
                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                       href="{{ route($item['route']) }}">
                        <i class="{{ $item['icon'] }} w-4 h-4"></i>

                        {{ $item['name'] }}
                    </a>
                @endforeach


                <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-red-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                   href="#" id="logout-btn">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5-5-5M19.8 12H9"/>
                    </svg>
                    {{ __('client.logout') }}
                </a>
            </div>
        </div>
@endif
