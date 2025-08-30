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

@php ($hasCustomer = \App\Models\Account\Customer::first() != null)
@extends('admin/layouts/admin')
@section('title',  __($translatePrefix . '.create.title'))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/tomselect.scss') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/tomselect.js') }}" type="module"></script>
    @if ($step == 2)
        @include('shared/js/options')
        <script>
        window.optionsPrices = @json($options_prices);
        window.taxPercent = '{{ tax_percent($item->currency) }}';
        window.recurrings = @json(['setupfee' => __('store.setup_price'), 'recurring' => __('store.config.recurring_payment'), 'onetime' => __('store.config.onetime_payment')]);
        window.per = '{{ __('store.per') }}';

        document.addEventListener('DOMContentLoaded', function () {

            if (window.optionsPrices === undefined) {
                return;
            }
            /** @var Object */
            const OptionsPrices = Object.entries(window.optionsPrices);
            const billingElement = document.querySelector('#billing');
            const pricings = JSON.parse(window.pricings);
            billingElement.addEventListener('change', function () {
                const pricing = pricings[this.value];
                updateLabels(pricing, OptionsPrices);
            });
            const pricing = pricings[billingElement.value];
            updateLabels(pricing, OptionsPrices);
        });

    </script>
    @else
    <script>
        /** @type {Object<string>} */
        window.productTypes = JSON.parse('@json($productTypes)');
        const typeElement = document.querySelector('#type');
        const inOptionInSelect = (select, value) => {
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === value) {
                    return true;
                }
            }
            return false;
        }

        const onChanges = (typeElement, productTypes, value) => {
            let product = productTypes[value];
            let type = product ? product : undefined;
            if (type != undefined && inOptionInSelect(typeElement, type)) {
                typeElement.value = type;
            }
        }
        document.querySelector('#product_id').addEventListener('change', function() {
            onChanges(typeElement, window.productTypes, this.value);
        });
        onChanges(typeElement, window.productTypes, document.querySelector('#product_id').value);
        @endif
    </script>
@endsection
@section('content')
    <div class="container mx-auto">

        <div class="mx-auto">
            @include('admin/shared/alerts')
            <form method="{{ $step == 1 ? 'GET' : 'POST' }}" action="{{ route($step == 1 ? $routePath . '.create' : $routePath .'.store') }}">
                <div class="card">
                    @if ($step == 2)
                        @csrf
                    @endif
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
                            @if ($step == 1 && $hasCustomer && !empty($products))
                            <button class="btn btn-primary" name="add">
                                {{ __('global.next') }}
                            </button>
                            @endif
                        </div>
                    </div>
                        @if ($step == 2 || $hasCustomer && !empty($products))

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        @if ($step == 1) <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'product_id', 'label' => __('global.product'), 'options' => $products, 'value' => old('product_id', $product_id)])
                            </div>
                        <div class="flex flex-col">
                            @include('admin/shared/search-field', ['name' => 'customer_id', 'label' => __('global.customer'), 'apiUrl' => route('admin.customers.search'), 'options' => $item->customer ? [$item->customer_id => $item->customer->excerptFullName()] : [], 'value' => $item->customer_id ])
                        </div>

                            <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'type', 'label' => __('provisioning.admin.services.show.type'), 'options' => $types, 'value' => $item->type])
                            </div>
                        @else
                            <div class="flex flex-col">
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name ?? '')])
                            </div>

                            <div class="flex flex-col">
                                @include('admin/shared/flatpickr', ['name' => 'expires_at', 'label' => __('global.expiration'), 'value' => $item->expires_at ? $item->expires_at->format('Y-m-d H:i:s') : null])
                            </div>

                            <div class="flex flex-col">
                                @include('admin/shared/select', ['name' => 'server_id', 'label' => __('global.server'), 'options' => $servers, 'value' => old('server_id', $item->server_id ?? 'none')])
                            </div>
                        <div class="flex flex-col">
                            @include('/admin/shared/textarea', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description), 'help' => __('provisioning.admin.services.show.description_help')])
                        </div>
                            <div class="flex flex-col">
                                @include('admin/shared/textarea', ['name' => 'notes', 'label' => __('provisioning.admin.services.show.notes'), 'value' => old('notes', $item->notes), 'help' => __('provisioning.admin.services.show.notes_help')])
                            </div>
                            <input type="hidden" name="customer_id" value="{{ $customer_id }}">
                            <input type="hidden" name="product_id" value="{{ $item->product_id ?? 'none' }}">
                            <input type="hidden" name="type" value="{{ $item->type ?? 'none' }}">
                            <input type="hidden" name="status" value="pending">
                            <div class="flex flex-col">
                                @include('admin/shared/input', ['name' => 'max_renewals', 'label' => __('provisioning.admin.services.show.max_renewals'), 'value' => $item->max_renewals, 'type' => 'number', 'help' => __('admin.blanktonolimit')])

                                <div>
                                    <label for="billing" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.recurrences') }}</label>
                                    <div class="relative mt-2">
                                        <select id="billing" name="billing" class="py-3 px-4 ps-9 pe-20 input-text">
                                            @foreach(app(\App\Services\Store\RecurringService::class)->getRecurrings() as $k => $v)
                                                <option value="{{ $k }}" @if($k == $item->billing) selected @endif>{{ __($v['translate']) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 end-0 flex items-center text-gray-500 pe-px">
                                            <label for="currency" class="sr-only">{{ __('global.currency') }}</label>
                                            <select id="currency" name="currency" class="store w-full border-transparent rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-400">
                                                @foreach(currencies() as $currency)
                                                    <option value="{{ $currency['code'] }}" @if($currency['code'] == $item->currency) selected @endif>{{ $currency['code'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @error('currency')
                                    <span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
                                    @enderror
                                </div>
                            </div>
                    </div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mt-2">
                                {{ __('admin.products.tariff') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __($pricing->related_type == 'product' ? $translatePrefix . '.pricing_synchronized' : $translatePrefix . '.pricing_custom') }}
                            </p>
                            @include('admin/shared/pricing/table', ['fees' => false])

                    @endif
                </div>
                @else

                    <div class="col-span-12">

                        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                            <i class="bi bi-{{ empty($customers) ? 'people' : 'box2' }} text-6xl text-gray-800 dark:text-gray-200"></i>
                            <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                {{ __(empty($customers) ? 'admin.customers.create.create_customer_help' : 'admin.products.create.create_product_help') }}
                            </p>
                            <a href="{{ empty($customers) ? route('admin.customers.create') : route('admin.products.create') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __(empty($customers) ? 'admin.customers.create.title' : 'admin.products.create.title') }}</a>
                        </div>
                    </div>
                @endif
                @if ($step == 2)
                    @if (!empty($options_html))
                        <div class="card border-b border-gray-900/10 pb-6">
                            <h2 class="text-lg font-semibold">{{ __('store.config.options') }}</h2>
                            {!! $options_html !!}
                        </div>
                    @endif
                <div class="card">
                    <nav class="relative z-0 flex border border-gray-200 rounded-xl overflow-hidden dark:border-gray-700" aria-label="Tabs" role="tablist" aria-orientation="horizontal">
                        <button type="button" class="hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 border-gray-200 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-hidden focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-l-gray-700 dark:border-b-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-400 active" id="bar-create-item" aria-selected="true" data-hs-tab="#bar-create" aria-controls="bar-create" role="tab">
                        {{ __($translatePrefix . '.create.new') }}
                        </button>
                        @if (!empty($importHTML))
                        <button type="button" class="hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 border-gray-200 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-hidden focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-l-gray-700 dark:border-b-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-400" id="bar-import-item" aria-selected="false" data-hs-tab="#bar-import" aria-controls="bar-import" role="tab">
                            {{ __($translatePrefix . '.create.import') }}
                        </button>
                        @endif
                    </nav>

                    <div class="mt-3">
                        <div id="bar-create" role="tabpanel" aria-labelledby="bar-create-item">
                            @if ($dataHTML != null)
                                {!! $dataHTML !!}
                            @endif

                                <button class="btn btn-primary mt-2" name="create">
                                    {{ __('admin.create') }}
                                </button>
                        </div>
                        <div id="bar-import" class="hidden" role="tabpanel" aria-labelledby="bar-import-item">
                            @if ($importHTML != null)
                                {!! $importHTML !!}
                            @endif
                            <button class="btn btn-primary mt-2" name="import">
                                {{ __($translatePrefix . '.create.importbtn') }}
                            </button>
                        </div>
                    </div>
                </div>
                    @endif
            </form>
        </div>
    </div>
    @include('admin/shared/pricing/collapse')


@endsection
