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
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

        <div class="mx-auto">
            @include('admin/shared/alerts')
            <form method="POST" action="{{ route($routePath . '.store') }}">
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
                            <button class="btn btn-primary">
                                {{ __('admin.create') }}
                            </button>
                        </div>
                    </div>
                    <div class="grid gap-2 md:grid-cols-2 gap-2 grid-cols-1">
                        <div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    @include('admin/shared/input', ['name' => 'firstname', 'label' => __('global.firstname'), 'value' => old('firstname', $item->firstname)])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'lastname', 'label' => __('global.lastname'), 'value' => old('lastname', $item->lastname)])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'balance', 'label' => __('global.balance'), 'value' => old('balance', $item->balance), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                                </div>
                            </div>
                            <div class="grid grid-cols-1">

                                <div class="mt-4">
                                    @include('admin/shared/input', ['name' => 'email', 'label' => __('global.email'), 'value' => old('email', $item->email), 'type' => 'email'])
                                </div>
                            <div class="mt-2">
                                @include('admin/shared/password', ['generate' => true, 'name' => 'password', 'label' => __('global.password'), 'value' => old('password'), 'help' => __('admin.admins.create.emptyforactive')])
                            </div>
                            </div>
                        </div>
                        <div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div class="sm:col-span-1">
                                    @include("admin/shared/input", ["name" => "address", "label" => __('global.address'), 'value' => old('address', $item->address)])
                                </div>
                                <div class="sm:col-span-1">
                                    @include("admin/shared/input", ["name" => "address2", "label" => __('global.address2'), 'value' => old('address2', $item->address2)])
                                </div>

                                <div>
                                    @include("admin/shared/input", ["name" => "zipcode", "label" => __('global.zip'), 'value' => old('zipcode', $item->zipcode)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "phone", "label" => __('global.phone'), 'value' => old('phone', $item->phone)])
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    @include("admin/shared/select", ["name" => "country", "label" => __('global.country'), "options" => $countries, "value" => old('country', $item->country)])
                                </div>

                                <div>
                                    @include("admin/shared/input", ["name" => "city", "label" => __('global.city'), 'value' => old('city', $item->city)])
                                </div>

                                <div>
                                    @include("admin/shared/input", ["name" => "region", "label" => __('global.region'), 'value' => old('region', $item->region)])
                                </div>
                                <div>
                                    @include("admin/shared/select", ["name" => "locale", "label" => __('global.locale'), 'value' => old('locale', $item->locale), "options" => $locales])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
