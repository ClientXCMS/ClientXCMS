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

@extends('admin/layouts/auth')
@section('title', __('auth.login.title'))
@section('content')
    <div class="p-4 sm:p-7">
        @include('admin.shared.alerts')
        <div class="text-center">
            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('admin.license.title') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.license.subheading') }}</p>
        </div>

        <div class="mt-5">
            <a class="btn btn-primary block w-full" href="{{ $oauth_url }}">{{ __('admin.license.click_to_activate') }}</a>
        </div>
    </div>
@endsection
