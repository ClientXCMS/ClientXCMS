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
@php($container = 'max-w-lg')


@extends('layouts/auth')
@section('title', __('auth.confirm_register.heading'))
@section('content')
    <div class="p-4 sm:p-7">

        @if($success = Session::get('success'))

            <div class="alert text-green-800 bg-green-100 mb-2" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                {!! $success !!}
            </div>
        @endif
        <div class="text-center max-w-96 mx-auto">

            <div class="mb-4">
                <i class="bi bi-envelope-check text-5xl text-green-500"></i>
            </div>
            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">{{ __('auth.confirm_register.heading') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.confirm_register.subheading', ['email' => $email]) }}
            </p>
            <div class="mt-4 text-left mx-auto">
                <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-3">{{ __('auth.confirm_register.help.not_received') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li class="flex gap-x-3">
    <span class="size-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </span>
                        <span class="text-gray-800 dark:text-neutral-400">
      {{ __('auth.confirm_register.help.check_spam') }}
    </span>
                    </li>

                    <li class="flex gap-x-3">
    <span class="size-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </span>
                        <span class="text-gray-800 dark:text-neutral-400">
      {{ ucfirst(__('auth.confirm_register.help.firewall')) }}
    </span>
                    </li>

                    <li class="flex gap-x-3">
    <span class="size-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </span>
                        <span class="text-gray-800 dark:text-neutral-400">
       {{ ucfirst(__('auth.confirm_register.help.valid_email')) }}
    </span>
                    </li>

                </ul>

                <a class="mt-6 w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('verification.send') }}">
                    <i class="bi bi-envelope-at"></i>
                    {{ ucfirst(__('auth.confirm_register.help.request_another')) }}
                </a>
            </div>

        </div>
    </div>
@endsection
