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

    <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full ">
<head>
    {{-- ... --}}
    <title>@yield('title') {{ translated_setting('seo_site_title') }}</title>
    @yield('styles')
    @vite('resources/themes/default/css/app.scss')
    @vite('resources/themes/default/js/app.js')
    @include('shared.a11y-styles')
    {!! app('seo')->head('front', $meta_append ?? null) !!}
    {!! app('seo')->favicon('front') !!}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="{{is_darkmode() ? 'dark' : '' }} flex flex-col h-full">
    <a href="#content" class="a11y-skip-link">{{ __('a11y.skip_to_content') }}</a>
    {!! app('seo')->header() !!}

<div class="dark:bg-gray-900 h-full">
    <main id="content" role="main" class="shrink-0">
        <div class="overflow-hidden">
            <header class="flex flex-wrap sm:justify-start sm:flex-nowrap z-50 w-full py-2.5 sm:py-4 bg-white border-b border-gray-200 text-sm py-3 sm:py-0 dark:bg-gray-800 dark:border-gray-700 print:hidden">
                <nav class="max-w-7xl flex min-w-0 basis-full items-center mx-auto px-4 sm:px-6 lg:px-8" aria-label="Global">
                    <div class="min-w-0 flex-1 me-3 sm:me-5 md:me-8">
                        @if (setting('theme_header_logo', false))
                            <a class="block text-xl font-semibold dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" aria-label="{{ setting('app_name') }}">
                                <img class="mx-auto h-10 w-auto max-w-full" src="{{ setting('app_logo_text', asset('images/logo.png')) }}" alt="{{ setting('app_name') }}">
                            </a>
                        @else
                            <a class="block truncate text-base font-semibold sm:text-xl dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" aria-label="{{ setting('app_name') }}">{{ setting('app_name') }}</a>
                        @endif
                    </div>
                    <div id="mobile-menu" class="hs-overlay hs-overlay-open:translate-x-0 translate-x-full hidden fixed top-0 end-0 transition-all duration-300 transform h-full w-full z-[60] overflow-y-auto bg-white border-s basis-full grow sm:order-2 sm:static sm:block sm:h-auto sm:w-auto sm:overflow-visible sm:border-s-transparent sm:transition-none sm:translate-x-0 sm:z-40 sm:basis-auto dark:bg-gray-800 dark:border-s-gray-700 sm:dark:border-s-transparent" tabindex="-1">
                        <div class="flex items-center justify-between border-b px-4 py-3 sm:hidden dark:border-gray-700">
                            <span class="truncate font-semibold text-gray-800 dark:text-white">{{ setting('app_name') }}</span>
                            <button type="button" class="btn-icon2 size-11 sm:size-9" data-hs-overlay="#mobile-menu" aria-label="{{ __('a11y.close_menu') }}">
                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex flex-col gap-y-1 gap-x-0 mt-2 px-2 pb-4 sm:flex-row sm:items-center sm:justify-end sm:gap-y-0 sm:mt-0 sm:px-0 sm:pb-0 sm:ps-7">
                            @foreach (app('theme')->getFrontLinks() as $link)
                                <a class="flex min-h-11 items-center rounded-lg px-3 font-medium hover:bg-gray-100 sm:min-h-0 sm:px-2 sm:mr-3 sm:hover:bg-transparent dark:hover:bg-gray-700 sm:dark:hover:bg-transparent {{ is_subroute($link->trans('url')) ? 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}" href="{{ $link->trans('url') }}">
                                    {!! $link->getHtmlIcon() !!} {{ $link->trans('name') }}
                                    @if (isset($link->badge))
                                        <span class="inline ms-1 font-medium text-xs bg-indigo-600 text-white py-1 px-2 rounded full">{{ $link->trans('badge') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        @auth('web')
                            <div class="mt-2 border-t px-2 pt-2 sm:hidden dark:border-gray-700">
                                @foreach(\App\Http\Navigation\ClientNavigationMenu::getItems() as $item)
                                    <a class="flex min-h-11 items-center gap-x-3.5 rounded-lg px-3 text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" href="{{ route($item['route']) }}">
                                        <i class="{{ $item['icon'] }}"></i> {{ $item['name'] }}
                                    </a>
                                @endforeach
                            </div>
                            <div class="mt-2 border-t px-2 pt-2 pb-4 sm:hidden dark:border-gray-700">
                                <p class="px-3 pb-1 text-xs text-gray-500 dark:text-gray-400">{{ __('auth.signed_in_as') }}</p>
                                <p class="truncate px-3 pb-2 text-sm font-medium text-gray-800 dark:text-gray-300">{{ auth('web')->user()->email }}</p>
                                <button type="submit" form="logout-form" class="flex min-h-11 w-full items-center gap-x-3.5 rounded-lg px-3 text-sm text-gray-800 hover:bg-red-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5-5-5M19.8 12H9"/></svg>
                                    {{ __('client.logout') }}
                                </button>
                            </div>
                        @endauth
                    </div>

                    <div class="flex flex-none items-center justify-end ms-auto sm:justify-between sm:gap-x-3 sm:order-3">
                        <div class="flex flex-row items-center justify-end gap-1 sm:gap-2">
                            @include('shared.layouts.iconright')
                        </div>

                        <div class="sm:hidden">
                            <button type="button" class="size-11 sm:size-9 flex justify-center items-center text-sm font-semibold rounded-lg text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-neutral-700 dark:hover:bg-neutral-700 p-1" data-hs-overlay="#mobile-menu" aria-controls="mobile-menu" aria-label="{{ __('a11y.open_menu') }}">
                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </nav>
            </header>

        </div>
        @yield('content')

    </main>
    @include('layouts.footer')
</div>
@yield('scripts')
{!! app('seo')->foot('front') !!}
<form method="POST" action="{{ route('logout') }}" id="logout-form">
    @csrf
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

    document.querySelectorAll('.confirmation-popup').forEach(
        function (element) {
            element.addEventListener('submit', function (event) {
                event.preventDefault();
                confirmation(element).then((result) => {
                    if (result.isConfirmed) {
                        element.submit();
                    }
                });
            });
        }
    )
    function confirmation(element) {
        const text = element.getAttribute('data-text') ?? '{{ __('admin.doyouwantreally') }}';
        const icon = element.getAttribute('data-icon') ?? 'warning';
        const confirmButtonText = element.getAttribute('data-confirm-button-text') ?? '{{ __('global.delete') }}';
        const showCancelButton = element.getAttribute('data-show-cancel-button') ?? true;
        const cancelButtonText = element.getAttribute('data-cancel-button-text') ?? '{{ __('global.cancel') }}';
        return Swal.fire({
            text: text,
            icon: icon,
            confirmButtonText: confirmButtonText,
            showCancelButton: showCancelButton,
            cancelButtonText: cancelButtonText,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
        })
    }
</script>

</body>
</html>
