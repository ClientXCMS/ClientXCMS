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
@section('title', __('auth.forgot.title'))
@section('content')

    <div class="p-4 sm:p-7">
        <div class="text-center">
            @include('admin.shared.alerts')

            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('auth.forgot.title') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.forgot.subheading') }}
            </p>
        </div>
            <form method="POST" action="{{ route('admin.password.email') }}">
                @csrf
                <div class="space-y-12">
                    <div class="pb-6">
                        <div class="border-b border-gray-900/10 pb-6">
                            @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email"])
                        </div>
                        <button class="btn-primary block w-full">
                            {{ __('auth.forgot.btn') }}
                        </button>
                    </div>
                </div>
            </form>
    </div>
@endsection
