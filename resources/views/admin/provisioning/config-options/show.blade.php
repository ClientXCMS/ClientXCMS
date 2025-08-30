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
    <script>
        const type = document.querySelector('select[name="type"]');
        const key = document.querySelector('select[name="key"]');
        const inputContainer = document.querySelector('#input-container');
        const disabledMany = document.querySelector('#disabled-many');
        const toggleCustomKey = function(select_value) {
            if (select_value == 'custom') {
                document.querySelector('input[name="custom_key"]').parentNode.parentNode.style.display = 'block';
                document.querySelector('#grid-key').classList.remove('md:grid-cols-2');
                document.querySelector('#grid-key').classList.add('md:grid-cols-3');
            } else {
                document.querySelector('input[name="custom_key"]').parentNode.parentNode.style.display = 'none';
                document.querySelector('#grid-key').classList.add('md:grid-cols-2');
                document.querySelector('#grid-key').classList.remove('md:grid-cols-3');
            }
        }
        const toggle = function(select_value) {

            if (select_value == 'slider') {
                document.querySelector('#only-slider').style.display = 'grid';
            } else {
                document.querySelector('#only-slider').style.display = 'none';
            }
            if (select_value == 'text' || select_value == 'textarea' || select_value == 'number' || select_value == 'custom' || select_value == 'slider' || select_value == 'checkbox') {
                inputContainer.style.display = 'grid';
                if (disabledMany)
                    disabledMany.style.display = 'grid';
            } else {
                if (disabledMany)
                    disabledMany.style.display = 'none';
                inputContainer.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            toggle(type.value);
            toggleCustomKey(key.value);
            type.addEventListener('change', function () {
                toggle(this.value);
            });
            key.addEventListener('change', function () {
                toggleCustomKey(this.value);
            });
        });
    </script>
    <script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>
@endsection
    @section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')
                    <form method="POST" action="{{ route($routePath . '.update', ['configoption' => $item]) }}">
                        <div class="card">
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
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                            </div>
                        </div>
                        @method('PUT')
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => $item->name])
                                @include('admin/shared/checkbox', ['name' => 'hidden', 'label' => __('global.hidden'), 'checked' => $item->hidden])
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    @include('admin/shared/search-select-multiple', ['name' => 'products[]', 'label' => __('global.products'), 'value' => old('products[]', $selectedProducts), 'options' => $products, 'multiple' => true])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'sort_order', 'label' => __('global.sort_order'), 'value' => $item->sort_order, 'type' => 'number'])
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="grid-key">
                            <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'key', 'label' => __('global.key'), 'value' => in_array($item->key, $keys->keys()->toArray()) ? $item->key : 'custom', 'options' => $keys])
                            </div>

                            <div>
                                @include('admin/shared/input', ['name' => 'custom_key', 'label' => __($translatePrefix .'.fields.custom_key'), 'value' => $item->key])
                            </div>
                            <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'type', 'label' => __('global.type'), 'options' => $types, 'value' => $item->type])
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="input-container">
                            <div>
                                @include('admin/shared/input', ['name' => 'default_value', 'label' => __($translatePrefix . '.fields.default_value'), 'value' => $item->default_value])
                                @include('admin/shared/checkbox', ['name' => 'required', 'label' => __($translatePrefix . '.fields.required'), 'checked' => $item->required])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'max_value', 'label' => __($translatePrefix . '.fields.max_value'), 'value' => $item->max_value])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'min_value', 'label' => __($translatePrefix . '.fields.min_value'), 'value' => $item->min_value])
                            </div>

                            <div>
                                @include('admin/shared/input', ['name' => 'rules', 'label' => __($translatePrefix . '.fields.rules'), 'value' => $item->rules])
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="only-slider">
                            <div>
                                @include('admin/shared/input', ['name' => 'step', 'label' => __($translatePrefix . '.fields.step'), 'value' => $item->step])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'unit', 'label' => __($translatePrefix . '.fields.unit'), 'value' => $item->unit])
                            </div>
                        </div>
                        </div>
                        @if (!in_array($item->type, ['dropdown', 'radio']))
                            <div class="card mt-2" id="disabled-many">
                                <div class="card-body">
                                    <div class="flex flex-col">
                                        <div class="-m-1.5 overflow-x-auto">
                                            @include('admin/shared/pricing/table')
                                                @if ($item->type == 'slider')
                                                    <span class="text-gray-500 text-xs italic mt-2">
                                                        @if ($item->step > 1)
                                                            {{ __($translatePrefix . '.per_step', ['step' => $item->step, 'unit' => $item->unit]) }}
                                                        @else
                                                        {{ __($translatePrefix . '.per_unit', ['unit' => $item->unit]) }}
                                                        @endif
                                                    </span>
                                                    @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                    </form>

                        @if (in_array($item->type, ['dropdown', 'radio']))
                            <form method="POST" action="{{ route($routePath . '.update_options', ['config_option' => $item]) }}" id="optionsForm">
                                @csrf
                        <div class="card mt-2">
                            <div class="card-body">
                                <div class="flex justify-between">
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __($translatePrefix . '.show.options.title') }}
                                    </h2>
                                    <div>
                                        <button type="submit" class="btn btn-primary" id="saveButton">{{ __('global.save') }}</button>
                                        <button class="btn btn-secondary" type="button" onclick="document.querySelector('#addOptionForm').submit();">{{ __($translatePrefix . '.show.options.add_option') }}</button>
                                    </div>
                                </div>
                                <ul  data-url="" is="sort-list" data-button="#saveButton" data-form="#optionsForm">
                                @foreach ($item->options as $option)
                                        <li id="{{ $option->id }}" class="sortable-item">

                                        <div class="card card-body my-3 p-3">
                                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                        <div class="col-span-3">
                                            @include('admin/shared/input', ['name' => 'options[' . $option->id . '][friendly_name]', 'label' => __($translatePrefix . '.show.options.friendly_name'), 'value' => $option->friendly_name])
                                        </div>
                                        <div class="col-span-2">
                                            @include('admin/shared/input', ['name' => 'options[' . $option->id . '][value]', 'label' => __('global.value'), 'value' => $option->value])
                                        </div>
                                                <div class="justify-end">
                                                    <button type="button" class="btn btn-danger" onclick="document.querySelector('#deleteOptionForm{{ $option->id }}').submit();">{{ __('global.delete') }}</button>
                                                    <div class="mt-4">

                                                    @include('admin/shared/checkbox', ['name' => 'options[' . $option->id . '][hidden]', 'label' => __('global.hidden'), 'value' => $option->hidden])
                                                    </div>
                                                </div>
                                                </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="-m-1.5 overflow-x-auto">
                                                    @include('admin/shared/pricing/table',['pricing_key' => "options[" .$option->id ."][pricing]", 'pricing' => $optionsPricing[$option->id]])
                                                </div>
                                            </div>
                                        </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </form>
                        @endif
    </div>
    @include('admin/shared/pricing/collapse')

        <form method="POST" action="{{ route($routePath . '.add_option', ['config_option' => $item]) }}" id="addOptionForm">
            @method('PUT')
            @csrf
        </form>
        @foreach($item->options as $option)
            <form method="POST" action="{{ route($routePath . '.destroy_option', ['config_option' => $item, 'option' => $option]) }}" id="deleteOptionForm{{ $option->id }}">
                @method('DELETE')
                @csrf
            </form>
        @endforeach
@endsection
