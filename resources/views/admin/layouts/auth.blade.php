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
<html class="{{is_darkmode(true) ? 'dark' : '' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- ... --}}
    <title>@yield('title') {{ setting('seo_site_title') }}</title>
    @yield('styles')
    @vite('resources/themes/default/js/app.js')
    @vite('resources/themes/default/css/app.scss')
    {!! app('seo')->head() !!}
    {!! app('seo')->favicon() !!}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
</head>
<body class="dark:bg-slate-900 bg-gray-100 flex h-full items-center py-16">
<main class="w-full {{ in_array(Route::current()->getName(), ['register', 'socialauth.finish']) ? 'max-w-6xl' : 'max-w-md' }} mx-auto p-6">
    <div class="mt-7 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <img class="mx-auto h-12 w-auto mt-4" src="{{ setting('app_logo_text') }}" alt="{{ setting('app_name') }}">
        @yield('content')
    </div>
</main>
@yield('scripts')
</body>
</html>
