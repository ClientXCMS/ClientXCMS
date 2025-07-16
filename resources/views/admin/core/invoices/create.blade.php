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
@extends('admin/layouts/admin')
@section('title',  __($translatePrefix . '.create.title'))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/tomselect.scss') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/tomselect.js') }}" type="module"></script>

@endsection
@section('content')
            @include('admin/shared/alerts')
                <form method="POST" action="{{ route($routePath .'.store') }}">
                    <div class="card">
                            @csrf
                    <div class="card-heading">
                        <div>

                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __($translatePrefix . '.create.title') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __($translatePrefix. '.create.subheading') }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                            @if (!empty($customers))
                            <button class="btn btn-primary">
                                {{ __('global.create') }}
                            </button>
                            @endif
                        </div>
                    </div>
                        @if (empty($customers))

                            <div class="col-span-12">

                                <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                    <i class="bi bi-people text-6xl text-gray-800 dark:text-gray-200"></i>
                                    <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                        {{ __('admin.customers.create.create_customer_help') }}
                                    </p>
                                    <a href="{{ route('admin.customers.create') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('admin.customers.create.title') }}</a>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            @include('admin/shared/search-field', ['name' => 'customer_id', 'label' => __('global.customer'), 'apiUrl' => route('admin.customers.search'), 'value' => $defaultCustomer])
                        </div>
                        <div>
                            @include("admin/shared/select", ['name' => 'currency', 'label' => __('global.currency'), 'options' => $currencies, 'value' => 'EUR'])
                        </div>
                    </div>
                            @endif
                    </div>
                </form>
@endsection
