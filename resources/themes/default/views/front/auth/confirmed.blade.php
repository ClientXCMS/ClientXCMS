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

@php($container = 'max-w-lg')

@extends('layouts/auth')
@section('title', __('auth.verified.heading'))
@section('content')
    <div class="p-4 sm:p-7">
        <div class="text-center max-w-96 mx-auto">

            <div class="mb-4">
                <i class="bi bi-person-fill-check text-5xl dark:text-green-500 text-green-600"></i>
            </div>
            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('auth.verified.heading') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.verified.subheading') }}
            </p>
            <div class="mt-4 text-left mx-auto">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">{{ __('auth.verified.allowed') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('auth.verified.support') }}</p>
                <a class="mt-6 w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ $redirect }}">
                    <i class="bi bi-{{ $translate == 'auth.verified.continue' ? 'person-circle' : 'cart2' }}"></i>
                    {{ __($translate) }}
                </a>
            </div>

        </div>
    </div>
@endsection
