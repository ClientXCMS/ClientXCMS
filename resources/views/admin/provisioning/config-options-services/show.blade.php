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
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->name]))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
@endsection
    @section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')
                    <form method="POST" class="card" novalidate action="{{ route($routePath . '.update', ['configoptions_service' => $item]) }}">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y')]) }}
                                </p>
                            </div>

                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                <a href="{{ route('admin.services.show', ['service' => $item->service]) }}" class="btn btn-secondary">
                                    {{ __($translatePrefix. '.show.see_service') }}
                                </a>
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                            </div>
                        </div>
                        @method('PUT')
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'config_option_id', 'label' => __('provisioning.config_option'), 'options' => $options, 'value' => $item->config_option_id])
                            </div>

                            <div class="flex flex-col">
                                @include('admin/shared/input', ['name' => 'value', 'label' => __('global.value'), 'value' => old('value', $item->value)])
                            </div>


                            <div class="flex flex-col">
                                @include('admin/shared/flatpickr', ['name' => 'expires_at', 'label' => __('global.expiration'), 'value' => old('expires_at', $item->expires_at)])
                            </div>
                            <div class="flex flex-col">
                                @include('admin/shared/input', ['name' => 'key', 'label' => __('global.key'), 'value' => old('key', $item->key), 'disabled' => true])
                            </div>
                        </div>

                        <div class="flex flex-col">
                            <div class="-m-1.5 overflow-x-auto">
                        @include('admin/shared/pricing/table')
                            </div>
                        </div>
                    </form>
    </div>
@endsection
