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
        <div class="text-center">
            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('client.profile.2fa.heading') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('client.profile.2fa.subheading') }}
            </p>
        </div>
        @include('admin.shared.alerts')

        <div class="mt-5">

            <form method="POST" action="{{ route('admin.auth.2fa') }}" id="captcha-form">

                @include("shared.input", ["name" => "2fa", "type" => "text", "label" => __('client.profile.2fa.code')])
                @include('shared.captcha')
                @csrf

                <button type="submit" class="btn-primary block w-full mt-2">
                    {{ __('auth.login.login') }}</button>
            </form>

        </div>
    </div>
@endsection
