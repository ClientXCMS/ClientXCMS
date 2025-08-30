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
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <form class="card" method="POST" action="{{ route($routePath .'.store') }}" enctype="multipart/form-data">
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
                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.create') }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'slug', 'label' => __('global.slug'), 'value' => old('slug', $item->slug)])
                            </div>
                            <div>
                                @include('admin/shared/select', ['name' => 'parent_id', 'label' => __($translatePrefix . '.parent_id'), 'value' => old('parent_id', $item->parent_id == null ? 'none' : ''), 'options' => $groups])
                            </div>
                            <div>
                                @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
                                @include('admin/shared/input', ['name' => 'sort_order', 'label' => __('global.sort_order'), 'value' => old('sort_order', $item->sort_order)])
                            </div>

                            <div>
                                @include('admin/shared/textarea', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description)])
                                <div class="mt-2">
                                    @include('admin/shared/checkbox', ['name' => 'pinned', 'label' => __('global.pinned'), 'checked' => old('pinned', $item->pinned)])
                                </div>
                                @include('admin/shared/checkbox', ['name' => 'use_image_as_background', 'label' => __($translatePrefix . '.use_image_as_background'), 'checked' => old('use_image_as_background', $item->hasMetadata('use_image_as_background'))])
                            </div>

                            <div>
                                @include('admin/shared/file', ['name' => 'image', 'label' => __($translatePrefix . '.image'), 'help' => __('admin.blanktochange')])
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </div>
@endsection
