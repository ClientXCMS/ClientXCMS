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
@section('title', __('auth.reset.title'))
@section('content')


    <div class="p-4 sm:p-7">
        <div class="text-center">
            @include('admin.shared.alerts')

            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('auth.reset.title') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.reset.subheading') }}
            </p>
        </div>
        <form method="POST" action="{{ route('admin.password.store') }}" class="captcha-form">
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                @csrf
                <div class="space-y-12">
                    <div class="pb-6">
                        <div class="border-b border-gray-900/10 pb-6">
                            @include("admin.shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => $request->get('email')])
                            <div class="mt-3">
                            @include("admin.shared.password", ["name" => "password", "label" => __('global.password'), "generate" => true])
                            </div>
                            <div class="mt-3">
                            @include("admin.shared.password", ["name" => "password_confirmation", "label" => __('global.password_confirmation'), "generate" => true])
                            </div>
                            @include('shared.captcha')
                        </div>
                        <button class="btn-primary block w-full">
                            {{ __('auth.reset.btn') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
@endsection
