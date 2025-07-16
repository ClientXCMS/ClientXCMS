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
<!doctype html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- ... --}}
    <title>@yield('title') - {{ config('app.name') }}</title>
    @yield('styles')
    @vite('resources/themes/default/js/app.js')
    @vite('resources/themes/default/css/app.scss')
</head>
<body class="dark:bg-slate-900 bg-gray-100 flex h-full items-center py-16">
<main class="w-full {{ Route::current()->getName() == 'register' ? 'max-w-6xl' : 'max-w-md' }} mx-auto p-6">
    <div class="mt-7 p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
<div data-hs-stepper>

    <a class="flex-none text-xl font-semibold dark:text-white" href="#" aria-label="CLIENTXCMS">
        <img src="{{ Vite::asset('resources/global/clientxcms_text.png') }}" class="mb-3">
    </a>
    <!-- Stepper Nav -->
    <ul class="relative flex flex-row gap-x-2">
        @foreach (['settings', 'register', 'summary'] as $index => $_step)
        <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group">
      <span class="min-w-[28px] min-h-[28px] group inline-flex items-center text-xs align-middle">
        @if ($index + 1 < $step)
          <span class="bg-green-200 w-7 h-7 flex justify-center items-center flex-shrink-0 font-medium text-gray-800 rounded-full group-focus:bg-gray-200 dark:bg-gray-700 dark:text-white dark:group-focus:bg-gray-600 hs-stepper-active:bg-blue-600 hs-stepper-active:text-white hs-stepper-success:bg-blue-600 hs-stepper-success:text-white hs-stepper-completed:bg-teal-500 hs-stepper-completed:group-focus:bg-teal-600">
          <svg class="flex-shrink-0 h-3 w-3 hs-stepper-success:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
          @else
              <span class="w-7 h-7 flex justify-center items-center flex-shrink-0 bg-gray-100 font-medium text-gray-800 rounded-full group-focus:bg-gray-200 dark:bg-gray-700 dark:text-white dark:group-focus:bg-gray-600 hs-stepper-active:bg-blue-600 hs-stepper-active:text-white hs-stepper-success:bg-blue-600 hs-stepper-success:text-white hs-stepper-completed:bg-teal-500 hs-stepper-completed:group-focus:bg-teal-600">
          <span class="hs-stepper-success:hidden hs-stepper-completed:hidden">{{ $index + 1 }}</span>
          <svg class="hidden flex-shrink-0 h-3 w-3 hs-stepper-success:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
          @endif
        <span class="ms-2 text-sm font-medium text-gray-800 dark:text-white">
          {{ __('install.'. $_step . '.title' ) }}
        </span>
      </span>
            <div class="w-full h-px flex-1 bg-gray-200 group-last:hidden hs-stepper-success:bg-blue-600 hs-stepper-completed:bg-teal-600"></div>
        </li>
@endforeach
    </ul>

    <div class="mt-5 sm:mt-8">
        @yield('content')
    </div>
</div>
    </div>
</main>
</body>
</html>
