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
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->name]))
@php($products = $item->products()->orderBy('sort_order')->get())
@php($group = $item)
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <form class="card" method="POST" action="{{ route($routePath .'.update', ['group' => $item]) }}" enctype="multipart/form-data">
                        <div class="card-heading">
                                @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            @method('PUT')
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y')]) }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                                @if (staff_has_permission('admin.manage_metadata'))

                                <button class="btn btn-secondary text-left" type="button" data-hs-overlay="#metadata-overlay">
                                    <i class="bi bi-database mr-2"></i>
                                    {{ __('admin.metadata.title') }}
                                </button>
                                @endif
                                <a href="{{ $group->route() }}" target="_blank" class="btn btn-dark dark:btn-white text-sm">
                                    {{ __($translatePrefix . '.see_group') }}
                                </a>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name), 'translatable' => true])
                            </div>

                            <div>
                                @include('admin/shared/input', ['name' => 'slug', 'label' => __('global.slug'), 'value' => old('slug', $item->slug), 'translatable' => true])
                            </div>
                                <div>
                                @include('admin/shared/select', ['name' => 'parent_id', 'label' => __($translatePrefix . '.parent_id'), 'value' => old('parent_id', $item->parent_id == null ? 'none' : $item->parent_id), 'options' => $groups])

                                </div>
                            <div>
                                @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
                                @include('admin/shared/input', ['name' => 'sort_order', 'label' => __('global.sort_order'), 'value' => old('sort_order', $item->sort_order)])

                                <div class="mt-2 flex">
                                    <input type="text" readonly class="input-text" id="group_url" value="{{ $group->route()  }}">
                                    <button type="button" data-clipboard-target="#group_url" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                        <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>

                                        <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>

                                    </button>
                                </div>
                            </div>
                            <div>
                                @include('admin/shared/textarea', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description), 'translatable' => true])
                                <div class="mt-2">
                                    @include('admin/shared/checkbox', ['name' => 'pinned', 'label' => __('global.pinned'), 'checked' => old('pinned', $item->pinned)])
                                    @include('admin/shared/checkbox', ['name' => 'use_image_as_background', 'label' => __($translatePrefix . '.use_image_as_background'), 'checked' => old('use_image_as_background', $item->hasMetadata('use_image_as_background'))])

                                </div>
                            </div>

                            <div>
                                @include('admin/shared/file', ['name' => 'image', 'label' => __($translatePrefix . '.image'), 'help' => __('admin.blanktochange'), 'canremove' => true, 'canRemove' => true])
                            </div>
                        </div>
                    </form>
                    @if(staff_has_permission('manage_products') && $products->count() > 0)
<div class="card mt-2">
    <div class="card-heading">
        <div>

        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ __('global.products') }}
        </h2>
        <h3 class="text-sm text-gray-600 dark:text-gray-400">
            {{ __($translatePrefix . '.order_products') }}
        </h3>
        </div>
        <button type="button" class="btn btn-primary" id="saveButton">{{ __('global.save') }}</button>

    </div>
                    <ul  data-url="{{ route('admin.groups.sort') }}" is="sort-list" data-button="#saveButton" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:items-center">
                    @foreach($products->where('status', 'active')->chunk(3) as $row)
                            @foreach($row as $product)
                                <li id="{{ $product->id }}" class="sortable-item">
                                    @if($product->pinned)
                                        @include($viewPath .'.products.pinned')
                                    @else
                                        @include($viewPath .'.products.product')
                                    @endif
                                </li>
                            @endforeach
                    @endforeach
                    </ul>
                </div>
                </div>
                @endif
                @if (staff_has_permission('admin.manage_products') && $products->count() == 0)
                    <div class="card mt-2">
                        <div class="card-heading">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('global.products') }}
                            </h2>
                            <h3 class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('store.product.noproduct') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('admin.products.create') }}?group_id={{ $group->id }}" class="btn btn-primary">
                                {{ __($translatePrefix . '.add_product') }}
                            </a>
                        </div>
                    </div>
                @endif

            </div>
    </div>
    @include('admin/metadata/overlay', ['item' => $group])
    @include('admin/translations/overlay', ['item' => $item])
@endsection
