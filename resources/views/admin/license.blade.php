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

@extends('admin/layouts/admin')
@section('title', __('admin.license.title'))
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('admin.license.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('admin.license.subheading2') }}
                                </p>
                            </div>
                            <div>
                                <a href="{{ $oauth }}"
                                   class="btn bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700">
                                    {{ __('admin.license.force') }}
                                </a>
                            </div>
                        </div>
                        <div class="">
                            <ul class="space-y-3 text-sm">
                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
      {{ __('admin.license.client_id') }} : {{ $client_id }}
    </span>
                                </li>

                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
                                @if ($license->get('expire') == null)
                                            {{ __('admin.license.onetime') }}
                                        @else
                                            {{ __('admin.license.expiration', ['date' => $license->get('expire')]) }}
                                        @endif
                                    </span>

                                </li>

                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
                                        @if (!$license->get('supportExpiration'))
                                            {{ __('admin.license.no_support') }}

                                            <a class="btn btn-secondary" href="https://clientxcms.com/pricing">
                                        {{ __('admin.license.buy_support') }}
                                    </a>
                                        @elseif ($license->get('supportExpiration')->isFuture())
                                            {{ __('admin.license.support_active', ['date' => $license->get('supportExpiration')->format('d/m/y')]) }}
                                        @else
                                            {{ __('admin.license.support_expired', ['date' => $license->get('supportExpiration')->format('d/m/y')]) }}

                                            <a class="btn btn-secondary" href="https://clientxcms.com/client/services">
                                        {{ __('admin.license.renew_support') }}
                                    </a>
                                        @endif
                                    </span>

                                </li>

                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
       {{ __('admin.license.type') }} : {{ $license->get('type') }}
    </span>
                                </li>

                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
       {{ __('admin.license.domain', ['domain' => $license->get('domain')]) }}
    </span>
                                </li>
                                <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                    <span class="text-gray-800 dark:text-gray-400">
       {{ __('admin.license.extensions', ['extensions' => $license->getFormattedExtensions()]) }}
    </span>
                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
