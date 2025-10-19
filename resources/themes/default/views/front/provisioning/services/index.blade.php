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

@extends('layouts/client')
@section('title', __('client.services.index'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/filter.js') }}"></script>
@endsection
@section('content')
    <div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @include('shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    @include('front/provisioning/services/card', ['services' => $services, 'filter' => $filter, 'filters' => $filters])
                </div>
                @if ($services->isNotEmpty())
                <div class="card">
                    <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                        @include("shared.icons.shopping-cart")
                        <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                            {{ __('client.services.orderservice') }}
                        </p>
                        <a href="{{ route('front.store.index') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('client.services.startorder') }}</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
