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
                disabledMany.style.display = 'grid';
            } else {
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
    @endsection
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12">
                <form method="POST" action="{{ route($routePath . '.store') }}">
                    <div class="card">
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
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => $item->name])
                        </div>
                        <div>
                            @include('admin/shared/search-select-multiple', ['name' => 'products[]', 'label' => __('global.products'), 'value' => old('products[]', []), 'options' => $products, 'multiple' => true])
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="grid-key">

                        <div class="flex flex-col">
                            @include('admin/shared/select', ['name' => 'key', 'label' => __('global.key'), 'value' => $item->key, 'options' => $keys])
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
                                @include('admin/shared/checkbox', ['name' => 'required', 'label' => __($translatePrefix . '.fields.required'), 'value' => $item->required])
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

                        <div class="card mt-2" id="disabled-many">
                            <div class="card-body">
                                <div class="flex flex-col">
                                    <div class="-m-1.5 overflow-x-auto">
                                        @include('admin/shared/pricing/table')
                                    </div>
                                </div>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>

    <div id="calculator" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.products.calculator.title') }}
            </h3>
            <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#calculator">
                <span class="sr-only">{{ __('global.close')  }}</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-4">
            <p class="text-gray-800 dark:text-gray-400">
                {{ __('admin.products.calculator.subheading') }}
                @include('admin/shared/input', ['name' => 'percentage', 'help' => __('admin.products.calculator.help'), 'label' => __('admin.products.calculator.percent'), 'value' => 5])
            </p>
            <button type="button" class="btn btn-primary mt-2" id="calculatorBtn" data-hs-overlay="#calculator">
                {{ __('admin.products.calculator.apply') }}
            </button>
        </div>
    </div>
@endsection
