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

@extends('install.layout')
@section('title', __('install.summary.title'))
@section('content')
    <form method="POST" action="{{ route('install.summary') }}">
        @csrf
    <ul class="mt-3 flex flex-col">
        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>ClientXCMS Version</span>
                <span>{{ ctx_version() }}</span>
            </div>
        </li>

        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>PHP Version</span>
                <span>{{ phpversion() }}</span>
            </div>
        </li>

        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>{{ __('install.email') }}</span>
                <span>{{ $email }}</span>
            </div>
        </li>

        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>{{ __('install.password') }}</span>
                <span>XXXX (encrypted)</span>
            </div>
        </li>

        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>{{ __('install.extensions') }}</span>
                <span>{{ $modules }}</span>
            </div>
        </li>
        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between w-full">
                <span>{{ __('install.authentication') }}</span>
                <span>{{ route('admin.login') }}</span>
            </div>
        </li>
    </ul>

    <button type="submit" class="mt-4 btn btn-primary w-full">
        {{ __('install.summary.btn') }}
    </button>
    </form>

@endsection
