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
@section('title',  __($translatePrefix . '.create.title', ['name' => $item->fullname]))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/editor.scss') }}">
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/monaco-editor.main.css') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/editor.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/product.js') }}" type="module"></script>

    <script>
        window.product = {
            value: @json(old('description', $item->description)),
            theme: {!! !is_darkmode(true) ? '"vs"' : '"vs-dark"' !!}
        }
    </script>
@endsection
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <form method="POST" action="{{ route($routePath .'.store') }}" enctype="multipart/form-data" id="product-form">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                                @csrf
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.create.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.create.subheading') }}
                                </p>
                            </div>
                            @if (!empty($groups))
                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.create') }}
                                </button>
                            </div>
                            @endif
                        </div>
                        @if (empty($groups))
                            <div class="col-span-12">

                                <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                    <i class="bi bi-shop text-6xl text-gray-800 dark:text-gray-200"></i>
                                    <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                        {{ __($translatePrefix . '.create.create_group_help') }}
                                    </p>
                                    <a href="{{ route('admin.groups.create') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('admin.groups.create.title') }}</a>
                                </div>
                            </div>
                        @else
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'stock', 'label' => __($translatePrefix . '.stock'), 'value' => old('stock', $item->stock)])
                            </div>
                            <div>
                                @include('admin/shared/select', ['name' => 'type', 'label' => __($translatePrefix . '.type'), 'value' => old('type', $item->type), 'options' => $types])
                            </div>
                            <div>
                                @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
                                @include('admin/shared/select', ['name' => 'group_id', 'label' => __($translatePrefix . '.group'), 'value' => old('group_id', $item->group_id), 'options' => $groups])
                                @include('admin/shared/input', ['name' => 'sort_order', 'label' => __('global.sort_order'), 'value' => old('sort_order', $item->sort_order)])
                                @include('admin/shared/file', ['name' => 'image', 'label' => __('admin.groups.image')])

                                <div class="mt-2">
                                    @include('admin/shared/checkbox', ['name' => 'pinned', 'label' => __('global.pinned'), 'checked' => old('pinned', $item->pinned)])
                                </div>
                            </div>

                            <div class="col-span-2">
                                @include('admin/shared/editor', ['name' => 'description', 'label' => __('global.description') . '<a href="#" id="toggle-btn" class="ml-5 btn btn-outline-primary btn-sm mb-2">HTML</a>', 'value' => old('description', $item->description), 'translatable' => true])

                                <div id="monaco-editor" style="height: 400px;display:none;"></div>
                                <input type="hidden" name="description_html" value="{{ old('description', $item->description) }}">

                            </div>

                        </div>
                        @endif
                    </div>
                    @if (!empty($groups))
                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="flex flex-col">

                                <div class="-m-1.5 overflow-x-auto">
                                    @include('admin/shared/pricing/table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        </form>
    </div>
    @include('admin/shared/pricing/collapse')
@endsection
