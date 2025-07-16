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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-bs-theme="{{ is_darkmode() ? 'dark' : 'light' }}">
<head>
    {{-- ... --}}
    <title>@yield('title') {{ translated_setting('seo_site_title') }}</title>
    @yield('styles')
    @vite('resources/themes/bootstrap/css/style.scss')
    @vite('resources/themes/bootstrap/js/app.js')
    {!! app('seo')->head('front', $meta_append ?? null) !!}
    {!! app('seo')->favicon('front') !!}
    <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="d-flex flex-column h-100">
<main class="flex-shrink-0">
    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg border-bottom">
        <div class="container px-5">
            <a class="navbar-brand" href="/">
                @if (setting('theme_header_logo', false))
                    <img src="{{ setting('app_logo_text', asset('images/logo.png')) }}" alt="{{ setting('app_name') }}" height="32">
                @else
                    {{ setting('app_name') }}
                @endif
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    @foreach(app('theme')->getFrontLinks() as $link)
                        <li class="nav-item">
                            <a href="{{ $link->url }}" class="nav-link {{ is_subroute($link->url) ? 'active' : '' }}">
                                <i class="{{ $link->trans('icon') }} me-1"></i> {{ $link->trans('name') }}
                                @if (isset($link->badge))
                                    <span class="inline ms-1 font-medium text-xs badge text-bg-primary text-white py-1 px-2 rounded-full">{{ $link->trans('badge') }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <ul class="navbar-nav navbar-right ms-auto">
                @include('shared.layouts.iconright')
            </ul>
        </div>
    </nav>
        <main class="container px-4 py-4 mx-auto">
            <div class="mb-2 text-center">
                <h4>@yield('title')</h4>
                @yield('redirect')
            </div>
        <div class="card card-primary">

                <div class="card-body">
                    @yield('content')
                </div>
            </div>
        </main>
</main>

@yield('scripts')

{!! app('seo')->foot('front') !!}
@include('includes/scripts')
</body>
</html>
