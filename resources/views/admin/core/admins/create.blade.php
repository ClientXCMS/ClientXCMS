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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'username', 'label' => __('global.username'), 'value' => old('username', $item->username)])
                            </div>

                            <div>
                                @include('admin/shared/input', ['name' => 'firstname', 'label' => __('global.firstname'), 'value' => old('firstname', $item->firstname)])
                            </div>

                            <div>
                                @include('admin/shared/input', ['name' => 'lastname', 'label' => __('global.lastname'), 'value' => old('lastname', $item->lastname)])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'email', 'label' => __('global.email'), 'value' => old('email', $item->email), 'type' => 'email'])
                                @include('admin/shared/select', ['name' => 'locale', 'label' => __('global.locale'), 'options' => $locales, 'value' => old('locale', $item->locale)])
                            </div>
                            <div>
                                @include('admin/shared/flatpickr', ['name' => 'expires_at', 'label' => __('global.expiration'),'help' => __('admin.blanktonolimit'), 'value' => old('expires_at', $item->expires_at)])
                            </div>
                            <div>
                                @include('admin/shared/password', ['name' => 'password', 'label' => __('global.password'), 'help' => __($translatePrefix. '.create.emptyforactive')])
                            </div>

                            <div>
                                @include('admin/shared/textarea', ['name' => 'signature', 'label' => __($translatePrefix . '.signature'), 'value' => old('signature', $item->signature)])
                            </div>
                            <div>
                                @include('admin/shared/select', ['name' => 'role_id', 'label' => __('admin.roles.role'), 'options' => $roles, 'value' => old('role', $item->role_id)])
                            </div>
                        </div>
                    </div>
                </form>
        </div>
    </div>

@endsection
