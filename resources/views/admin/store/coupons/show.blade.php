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
    <script src="{{ Vite::asset('resources/global/js/admin/productshow.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/tomselect.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>

@endsection
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/tomselect.scss') }}">
@endsection
@section('content')
    <div class="container mx-auto">

            @include('admin/shared/alerts')
            <form method="POST" action="{{ route($routePath .'.update', ['coupon' => $item]) }}" enctype="multipart/form-data">
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="card">
                                <div class="card-heading">
                                    @csrf
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
                                        <button class="btn btn-secondary text-left" type="button" data-hs-overlay="#metadata-overlay">
                                            <i class="bi bi-database mr-2"></i>
                                            {{ __('admin.metadata.title') }}
                                        </button>
                                        <button class="btn btn-primary">
                                            {{ __('admin.updatedetails') }}
                                        </button>

                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        @include('admin/shared/input', ['name' => 'code', 'label' => __($translatePrefix . '.code'), 'value' => old('code', $item->code)])
                                    </div>
                                    <div>
                                        @include('admin/shared/select', ['name' => 'type', 'label' => __('global.type'), 'value' => old('type', $item->type), 'options' => $types])
                                    </div>
                                    <div>
                                        @include('admin/shared/flatpickr', ['name' => 'start_at', 'label' => __($translatePrefix . '.start_at'), 'value' => old('start_at', $item->start_at ? $item->start_at->format('Y-m-d H:i:s') : null)])
                                    </div>
                                    <div>
                                        @include('admin/shared/flatpickr', ['name' => 'end_at', 'label' => __($translatePrefix . '.end_at'), 'value' => old('end_at', $item->end_at != null ? $item->end_at->format('Y-m-d H:i:s') : null)])
                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'applied_month', 'label' => __($translatePrefix . '.applied_month'), 'value' => old('applied_month', $item->applied_month), 'help' => __($translatePrefix . '.applied_month_help'), 'type' => 'number'])
                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'max_uses', 'label' => __($translatePrefix . '.max_uses'), 'value' => old('max_uses', $item->max_uses), 'type' => 'number', 'help' => __($translatePrefix . '.uses_help')])
                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'max_uses_per_customer', 'label' => __($translatePrefix . '.max_uses_per_customer'), 'value' => old('max_uses_per_customer', $item->max_uses_per_customer), 'type' => 'number', 'help' => __($translatePrefix . '.uses_help')])
                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'usages', 'label' => __($translatePrefix . '.usages'), 'value' => old('usages', $item->usages), 'type' => 'number'])
                                    </div>

                                    <div>
                                        @include('admin/shared/input', ['name' => 'minimum_order_amount', 'label' => __($translatePrefix . '.minimum_order_amount'), 'value' => old('minimum_order_amount', $item->minimum_order_amount), 'type' => 'number'])
                                    </div>

                                    <div>
                                        @include('admin/shared/search-select-multiple', ['name' => 'products[]', 'label' => __($translatePrefix . '.products'), 'value' => $selectedProducts, 'options' => $products])
                                    </div>

                                    <div>
                                        @include('admin/shared/search-select-multiple', ['name' => 'groups[]', 'label' => __($translatePrefix . '.groups'), 'value' => [], 'options' => $groups])
                                    </div>
                                    <div>
                                        @include('admin/shared/search-select-multiple', ['name' => 'required_products[]', 'label' => __($translatePrefix . '.required_products'), 'value' => $requiredProductSelected, 'options' => $requiredProducts])
                                    </div>
                                    <div>
                                        @include('admin/shared/search-field', ['name' => 'customer_id', 'label' => __($translatePrefix . '.allowed_customer'), 'apiUrl' => route('admin.customers.search'), 'options' => $item->customer ? [$item->customer_id => $item->customer->excerptFullName()] : [], 'value' => $item->customer_id])
                                    </div>

                                    <div>
                                        @include('admin/shared/checkbox', ['name' => 'is_global', 'label' => __($translatePrefix . '.is_global'), 'checked' => old('is_global', $item->is_global)])
                                        @include('admin/shared/checkbox', ['name' => 'free_setup', 'label' => __($translatePrefix . '.free_setup'), 'checked' => old('free_setup', $item->free_setup)])
                                        @include('admin/shared/checkbox', ['name' => 'first_order_only', 'label' => __($translatePrefix . '.first_order_only'), 'checked' => old('first_order_only', $item->first_order_only)])
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-2">
                                <div class="card-body">
                                    <div class="flex flex-col">
                                        <div class="-m-1.5 overflow-x-auto">
                                            <div class="p-1.5 min-w-full inline-block align-middle">
                                                <a href="#" class="text-primary" id="showmorepricingbtn">{{ __('admin.products.showmorepricing') }}</a>
                                                <div class="overflow-hidden">
                                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="pricingtable">
                                                        <thead>
                                                        <tr>
                                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase hidden sm:block">
                                                                <button class="btn btn-primary btn-sm" type="button" data-hs-overlay="#calculator"><i class="bi bi-calculator"></i></button>
                                                                {{ __('admin.products.tariff') }}
                                                            </th>
                                                            @foreach ($recurrings as $recurring)
                                                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                                                                    {{ $recurring['translate'] }}
                                                                </th>
                                                            @endforeach
                                                        </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200 ">
                                                                {{ __($translatePrefix . '.pricelabel') }}
                                                            </td>
                                                            @foreach ($recurrings as $k => $recurring)

                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                                                                    @include('admin/shared/input', ['name' => 'pricing['. $k .'][price]','type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('recurrings_' . $k . '_price', $pricing->{$k}), 'attributes' => ['data-months' => $recurring['months']]])
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                                                                {{ __('store.fees') }}
                                                            </td>
                                                            @foreach ($recurrings as $k => $recurring)

                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                                                                    @include('admin/shared/input', ['name' => 'pricing['. $k .'][setup]', 'type' => 'number','step' => '0.01', 'min' => 0, 'value' => old('recurrings_' . $k . '_setup', $pricing->{'setup_'.$k}), 'attributes' => ['data-months' => $recurring['months']]])
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($errors->has('pricing'))
                                                    <p class="text-red-500 text-xs italic mt-2">
                                                        {{ $errors->first('pricing') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-2">
                                <div class="card-body">

                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __($translatePrefix . '.usage') }}
                                    </h3>
                                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead>

                                            <tr>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      #
                    </span>
                                                    </div>
                                                </th>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.customer') }}
                    </span>
                                                    </div>
                                                </th>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __($translatePrefix . '.used_at') }}
                    </span>
                                                    </div>
                                                </th>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.amount') }}
                    </span>
                                                    </div>
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-start">
                                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">

                                        {{ __('global.actions') }}
                                                            </span>
                                                </th>
                                            </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @if (count($usages) == 0)
                                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center">
                                                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                                                {{ __('global.no_results') }}
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                            @foreach($usages as $usage)

                                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $usage->id }}</span>
                    </span>
                                                    </td>

                                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @if ($usage->customer)
                          <a href="{{ route('admin.customers.show', ['customer' => $usage->customer]) }}">
                          {{ $usage->customer->excerptFullName() }}
                        </a>
                            @else
                            {{ __('global.deleted') }}
                            @endif
                    </span>
                                                    </td>

                                                    <td class="h-px w-px whitespace-nowrap px-6">
                                            <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $usage->used_at->format('d/m/y H:i')  }}</span>
                    </span>
                                                    </td>

                                                    <td class="h-px w-px whitespace-nowrap px-6">
                                            <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($usage->amount)  }}</span>
                    </span>
                                                    </td>
                                                    <td class="h-px w-px whitespace-nowrap">

                                                        <form method="POST" action="{{ route($routePath . '.deleteusage', ['coupon_usage' => $usage]) }}" class="inline" onsubmit="return confirmation();">
                                                            @method('DELETE')
                                                            @csrf
                                                            <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">

                                              <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            {{ __('global.delete') }}
                                          </span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="py-1 px-4 mx-auto">
                                        {{ $usages->links('admin.shared.layouts.pagination') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div id="calculator" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __('admin.products.calculator.title') }}
                </h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#calculator">
                    <span class="sr-only">Close modal</span>
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
        @include('admin/metadata/overlay', ['item' => $item])
    @endsection
