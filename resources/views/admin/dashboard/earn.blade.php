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
@section('title', __('admin.dashboard.earn.title'))
@extends('admin.layouts.admin')
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/customcanvas.js')  }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script>
        const yearSelect = document.querySelector('select[name="year"]');
        const limitSelects = document.querySelectorAll('select[name="limit"]');
        yearSelect.addEventListener('change', function() {
            let params = new URLSearchParams(window.location.search);
            if (this.value == 'none'){
                params.delete('year');
            } else {
                params.set('year', this.value);
            }
            document.location = document.location.href.split('?')[0] + '?' + params.toString()
            document.reload();
        });
        limitSelects.forEach(limitSelect => {
            limitSelect.addEventListener('change', function() {
                let params = new URLSearchParams(window.location.search);
                if (this.value == 'none'){
                    params.delete('limit');
                } else {
                    params.set('limit', this.value);
                }
                document.location = document.location.href.split('?')[0] + '?' + params.toString()
                document.reload();
            });
        });

    </script>
@endsection
@section('content')
    <div class="container mx-auto">
    <nav class="relative z-0 flex border rounded-xl overflow-hidden dark:border-slate-700 flex-col md:flex-row" aria-label="Tabs" role="tablist">
        @foreach ($widgets as $name => $items)

        <button type="button" class="hs-tab-active:border-b-indigo-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-indigo-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-indigo-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400 {{ ($loop->first && !$isCustom) || ($loop->last && $isCustom) ? 'active' : '' }}" id="earn-title-{{ Str::slug($name) }}" data-hs-tab="#earn-tab-{{ Str::slug($name) }}" aria-controls="earn-tab-{{ Str::slug($name) }}" role="tab">
            {{ $name }}
        </button>
        @endforeach
    </nav>

    <div class="mt-3">
        @foreach ($widgets as $name => $items)
        <div id="earn-tab-{{ Str::slug($name) }}" class="{{ ($loop->first && !$isCustom) || ($loop->last && $isCustom) ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="earn-title-{{ Str::slug($name) }}">
                @if($loop->last)
                    <div class="grid md:grid-cols-6 border border-gray-200 shadow-sm overflow-hidden dark:border-slate-800">
                    <div class="flex col-span-{{ !empty($items) ? 2 : 6 }} card" style="margin-bottom: 0;">
                        <form>
                            <div class="flex grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                @include('admin/shared/flatpickr', ['name' => 'start','type' => 'date', 'label' => __('admin.dashboard.earn.start_date'), 'value' => $start->format('Y-m-d')])
                            </div>
                            <div>
                                @include('admin/shared/flatpickr', ['name' => 'end', 'type' => 'date', 'label' => __('admin.dashboard.earn.end_date'), 'value' => $end->format('Y-m-d')])
                            </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-4">{{ __('global.filter') }}</button>
                        </form>
                    </div>
                        @if (!empty($items))
                        <div class="md:col-span-4">
                            <div class="grid md:grid-cols-3 border border-gray-200 shadow-sm overflow-hidden dark:border-slate-800">
                                @foreach ($items as $widget)
                                    <a class="block p-4 md:p-5 relative bg-white hover:bg-gray-50 before:absolute before:top-0 before:start-0 before:w-full before:h-px md:before:w-px md:before:h-full before:bg-gray-200 before:first:bg-transparent dark:bg-gray-800 dark:hover:bg-gray-700 dark:before:bg-gray-700" href="#">
                                        <div class="flex md:grid lg:flex gap-y-3 gap-x-5">
                                            <i class="{{ $widget->icon }}"></i>
                                            <div class="grow">
                                                <p class="text-xs uppercase tracking-wide font-medium text-gray-800 dark:text-slate-200">
                                                    {{ $widget->title }}
                                                </p>
                                                <h3 class="mt-1 text-xl sm:text-2xl font-semibold text-indigo-600 dark:text-indigo-500">
                                                    {{ $widget->value }}
                                                </h3>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                            @endif
                    </div>
                @else
                    <div class="grid md:grid-cols-6 border border-gray-200 shadow-sm rounded-xl overflow-hidden dark:border-slate-800">
                    @foreach ($items as $widget)
                    <a class="block p-4 md:p-5 relative bg-white hover:bg-gray-50 before:absolute before:top-0 before:start-0 before:w-full before:h-px md:before:w-px md:before:h-full before:bg-gray-200 before:first:bg-transparent dark:bg-gray-800 dark:hover:bg-gray-700 dark:before:bg-gray-700" href="#">
                        <div class="flex md:grid lg:flex gap-y-3 gap-x-5">
                            <i class="{{ $widget->icon }}"></i>
                            <div class="grow">
                                <p class="text-xs uppercase tracking-wide font-medium text-gray-800 dark:text-slate-200">
                                    {{ $widget->title }}
                                </p>
                                <h3 class="mt-1 text-xl sm:text-2xl font-semibold text-indigo-600 dark:text-indigo-500">
                                    {{ $widget->value }}
                                </h3>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
        </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
        <div class="col-span-2">
            <div class="card-sm">
                @include('admin.dashboard.cards.best-selling', ['dto' => $bestSelling['dto'], 'week' => $bestSelling['week'], 'month' => $bestSelling['month']])
            </div>
            <div class="card-sm">
            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2 mt-2">{{ __('admin.dashboard.widgets.best_selling.title2') }}</h3>
                @if ($bestSelling['products']->isEmpty())
                    <p>{{ __("global.no_results") }}</p>
                @endif
            <div class="grid grid-cols-4 gap-2">

                <div class="col-span-2">
                    <ul class="mt-3 flex flex-col">
                        @for ($i = 0; $i < $bestSelling['split']; $i++)
                        <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-slate-700 dark:text-slate-200">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $bestSelling['products'][$i]->count }} x {{ $bestSelling['productsNames'][$bestSelling['products'][$i]->related_id] ?? 'Not found' }}</span>
                                <span>{{ formatted_price($bestSelling['products'][$i]->price) }}</span>
                            </div>
                        </li>
                        @endfor
                    </ul>
                </div>
                <div class="col-span-2">
                    <ul class="mt-3 flex flex-col">
                        @for ($i = $bestSelling['split']; $i < count($bestSelling['products']); $i++)
                            <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-slate-700 dark:text-slate-200">
                                <div class="flex items-center justify-between w-full">
                                    <span>{{ $bestSelling['products'][$i]->count }} x {{ $bestSelling['productsNames'][$bestSelling['products'][$i]->related_id] ?? 'Not found' }}</span>
                                    <span>{{ formatted_price($bestSelling['products'][$i]->price) }}</span>
                                </div>
                            </li>
                    @endfor
                </div>
            </div>
                <div class="py-1 px-4 mx-auto">
                    {{ $bestSelling['products']->links('admin.shared.layouts.pagination') }}
                </div>
        </div>

            <div class="card-sm">
                <div class="justify-between flex">
                <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2 mt-2">{{ __('admin.dashboard.earn.services.title') }}</h3>
                <form class="flex mb-2">
                    <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 my-auto mr-3">{{ __('admin.dashboard.earn.limit') }}</label>
                    @include('admin/shared/select', ['name' => 'limit', 'options' => $limits, 'value' => $limit])
                </form>
            </div>
                <div class="border rounded-lg overflow-hidden dark:border-gray-700">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>

                        <tr>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.name') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.services') }}
                    </span>
                                </div>
                            </th>



                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('admin.dashboard.earn.services.renewals_avg') }}
                    </span>
                                </div>
                            </th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @if ($servicesStatistics->products->isEmpty())
                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                        <p class="text-sm text-gray-800 dark:text-gray-400">
                                            {{ __('global.no_results') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @foreach($servicesStatistics->products as $item)

                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          <a href="{{ route('admin.products.show', ['product' => $item->product_id]) }}">
                                {{ $item->product->name }}
                          </a>
                      </span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $item->count }}
                      </span>
                    </span>
                                </td>


                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($item->renewals_avg, 2) }}</span>
                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if (method_exists($servicesStatistics->products, 'links'))
                    <div class="py-1 px-4 mx-auto">
                        {{ $servicesStatistics->products->links('admin.shared.layouts.pagination') }}
                    </div>
                @endif
            </div>

            <div class="card-sm col-span-1 row-span-1">
                <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('billing.admin.subscriptions.earn.subscription_and_services') }}</h3>

                        <div class="grid shadow-sm grid-cols-3 gap-3 rounded-xl mb-3 overflow-y-auto max-h-96">
                            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                <div class="p-4 md:p-5">
                                    <div class="flex items-center gap-x-2">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                            {{ __('admin.dashboard.earn.max_services_by_customer') }}
                                        </p>
                                        @if ($servicesStatistics->maxServicesByCustomer)
                                            <a href="{{ route('admin.customers.show', ['customer' => $servicesStatistics->maxServicesByCustomer->customer_id]) }}">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex items-center gap-x-2">
                                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                            {{ $servicesStatistics->maxServicesByCustomer ? $servicesStatistics->maxServicesByCustomer->count : 0 }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                <div class="p-4 md:p-5">
                                    <div class="flex items-center gap-x-2">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                            {{ __('admin.dashboard.earn.max_periods') }}
                                        </p>

                                        @if ($servicesStatistics->maxPeriods)
                                            <a href="{{ route('admin.services.show', ['service' => $servicesStatistics->maxPeriods->service_id]) }}">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex items-center gap-x-2">
                                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                            {{ $servicesStatistics->maxPeriods ? $servicesStatistics->maxPeriods->period : 0 }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                <p class="text-gray-500 font-semibold">{{ __('billing.admin.subscriptions.earn.service_subscription_help', ['date' => $servicesFrom->format('d/m/y')]) }}</p>

                            <div class="grid md:grid-cols-2 gap-3 shadow-sm rounded-xl mb-3 overflow-y-auto max-h-96">

                            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('admin.dashboard.earn.already_renewed') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ $servicesStatistics->already_renewed }}
                                                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-500">{{ $servicesStatistics->percentage('already_renewed', 'actives') }}% </span>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('admin.dashboard.earn.service_can_renewal') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ $servicesStatistics->actives }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('billing.admin.subscriptions.earn.monthly_earn') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ formatted_price($servicesStatistics->earn) }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('billing.admin.subscriptions.earn.monthly') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ formatted_price($servicesStatistics->subscriptions) }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('admin.dashboard.earn.average_periods') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ number_format($servicesStatistics->averagePeriods, 2) }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700">
                                    <div class="p-4 md:p-5">
                                        <div class="flex items-center gap-x-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-500">
                                                {{ __('admin.dashboard.earn.average_service_price') }}
                                            </p>
                                        </div>

                                        <div class="mt-1 flex items-center gap-x-2">
                                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-slate-200">
                                                {{ formatted_price($servicesStatistics->averageServicesPrice) }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                <p class="text-gray-500 font-semibold col-span-2">
                    {{ __('billing.admin.subscriptions.earn.monthly_help') }}
                </p>
                </div>
        </div>
    <div class="col-span-2 row-span-1">
        <div class="flex flex-col card-sm col-span-2">
        <div class="card-heading">
                <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('admin.dashboard.earn.gateway_canvas') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.dashboard.earn.gateway_canvas_help') }}</p>
            </div>
            <div class="grid md:grid-cols-2 border border-gray-200 shadow-sm rounded-xl mb-3 overflow-y-auto max-h-96 dark:border-slate-800">
                @if ($gatewaysSources->isEmpty())
                    <p class="p-4">{{ __("global.no_results") }}</p>
                @endif
                @foreach ($gatewaysSources->items as $gateway)
                    @if ($gateway['paymethod'] == 'none')
                        @continue
                    @endif
                        <div class="block p-4 md:p-5 relative bg-white dark:bg-gray-800 h-full flex">
                        <div class="flex md:grid lg:flex items-center gap-y-3 gap-x-5 grow">
                            <i class="{{ $gatewaysSources->icons[$gateway['paymethod']] }} text-3xl"></i>
                            <div class="grow">
                                <p class="text-xs uppercase tracking-wide font-medium text-gray-800 dark:text-slate-200">
                                    {{ $gatewaysSources->names[$gateway['paymethod']] }}
                                </p>
                                <h3 class="mt-1 text-xl sm:text-2xl font-semibold text-indigo-600 dark:text-indigo-500">
                                    {{ formatted_price($gatewaysSources->amounts[$gateway['paymethod']]) }}
                                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-500">{{ $gatewaysSources->percentages[$gateway['paymethod']] }}% | {{ $gatewaysSources->counts[$gateway['paymethod']] }} {{ $gatewaysSources->counts[$gateway['paymethod']] == 1 ? __('global.invoice') : __('global.invoices') }}</span>
                                </h3>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex flex-col col-span-2">
                <div class="card-heading">
                    <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('admin.dashboard.earn.related_billed.title') }}</h3>
                </div>

                <div class="grid md:grid-cols-2 mb-3 overflow-y-auto border border-gray-200 dark:border-slate-800">
                    @if ($relatedBilled->isEmpty())
                        <p class="p-4">{{ __("global.no_results") }}</p>
                    @endif
                    @foreach ($relatedBilled->items as $type)
                        <div class="p-4 md:p-5 relative bg-white dark:bg-gray-800 h-full flex">
                            <div class="flex md:grid lg:flex items-center gap-y-3 gap-x-5 grow">
                                <i class="{{ in_array($type['type'], array_keys($relatedBilled->icons)) ? $relatedBilled->icons[$type['type']] : 'bi bi-boxes' }} text-3xl"></i>
                                <div class="grow">
                                    <p class="text-xs uppercase tracking-wide font-medium text-gray-800 dark:text-slate-200">
                                        {{ $relatedBilled->names[$type['type']] ?? $type['type'] }}
                                    </p>
                                    <h3 class="mt-1 text-xl sm:text-2xl font-semibold text-indigo-600 dark:text-indigo-500">
                                        {{ formatted_price($relatedBilled->amounts[$type['type']]) }}
                                        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-500">{{ $relatedBilled->percentages[$type['type']] }}% | {{ trans_choice('admin.dashboard.earn.lines', $relatedBilled->counts[$type['type']], ['count' =>  $relatedBilled->counts[$type['type']]]) }}</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
    </div>
        <div class="card-sm">
            <div class="flex justify-between">
            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.dashboard.earn.graph_month') }}</h3>
            <form class="flex mb-2">
                <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 my-auto mr-3">{{ __('admin.dashboard.earn.compare_year') }}</label>
                @include('admin/shared/select', ['name' => 'year', 'options' => $years, 'value' => $year])
            </form>
            </div>
            <div class="chart-responsive">
        <canvas height="140" is="custom-canvas" data-labels='{!! $earnMonths->getLabels() !!}' data-backgrounds='{{ $earnMonths->getColors() }}' data-set='{!! $earnMonths->getValues() !!}' data-type="line" data-suffix="{{ currency_symbol() }}" data-titles="{!! $earnMonths->getTitles() !!}" title="{{ __('admin.dashboard.earn.graph_month') }}"></canvas>
            </div>
            </div>
        @foreach (['last_orders' => $lastorders, 'last_renewals' => $lastrenewals] as $key => $values)
        <div class="card-sm">

        <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.dashboard.earn.' . $key) }}</h3>
        <div class="border rounded-lg overflow-hidden dark:border-gray-700">

            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>

                <tr>


                    <th scope="col" class="px-6 py-3 text-start">
                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('store.total') }}
                    </span>
                        </div>
                    </th>

                    <th scope="col" class="px-6 py-3 text-start">
                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __($key == 'last_orders' ? 'client.invoices.itemname' : 'global.name') }}
                    </span>
                        </div>
                    </th>

                    <th scope="col" class="px-6 py-3 text-start">
                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.created') }}
                    </span>
                        </div>
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @if (count($values) == 0)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                        <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                <p class="text-sm text-gray-800 dark:text-gray-400">
                                    {{ __('global.no_results') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endif
                @foreach($values as $item)

                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($item->total, $item->currency) }}</span>
                    </span>
                        </td>
                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          <a href="{{ route('admin.invoices.show', ['invoice' => $item]) }}">
                          {{ collect($item->items)->where('type', $key == 'last_orders' ? 'service' : 'renewal')->pluck('name')->implode(', ') }}

                            </a>
                    </span>
                    </span>
                        </td>

                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at->format('d/m/y H:i') }}</span>
                    </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
        @endforeach
        <div class="card-sm">
            <div class="flex justify-between">

            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.dashboard.earn.best_customers') }}</h3>
            <form class="flex mb-2">
                <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 my-auto mr-3">{{ __('admin.dashboard.earn.limit') }}</label>
                @include('admin/shared/select', ['name' => 'limit', 'options' => $limits, 'value' => $limit])
            </form>
            </div>
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
                                            {{ __('store.total') }}
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
                      {{ __('admin.dashboard.earn.customer_from') }}
                    </span>
                            </div>
                        </th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @if ($bestCustomers->isEmpty())
                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                    <p class="text-sm text-gray-800 dark:text-gray-400">
                                        {{ __('global.no_results') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @foreach($bestCustomers as $i => $item)
                        @php ($offsetCustomers = method_exists($bestCustomers, 'perPage') ? $bestCustomers->perPage() * ($bestCustomers->currentPage() - 1) : 0)
                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                        @switch($offsetCustomers + $i)
                            @case (0)
                                <i class="bi bi-trophy text-yellow-500"></i>
                                @break
                            @case (1)
                                <i class="bi bi-trophy text-gray-500"></i>
                                @break
                            @case (2)
                                <i class="bi bi-trophy text-orange-500"></i>
                                @break
                            @default
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $offsetCustomers + $i + 1 }}

                                @break
                        @endswitch

                      </span>
                    </span>
                            </td>

                            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($item->total_subtotal) }}</span>
                    </span>
                            </td>
                            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                            <a href="{{ route('admin.customers.show', ['customer' => $item->customer_id]) }}">
                                {{ $item->customer->fullName }}
                            </a>
                    </span>
                    </span>
                            </td>

                            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->customer->created_at->format('d/m/y H:i') }}</span>
                    </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if (method_exists($bestCustomers, 'links'))
            <div class="py-1 px-4 mx-auto">
                {{ $bestCustomers->links('admin.shared.layouts.pagination') }}
            </div>
            @endif

        </div>
    </div>
    </div>
    </div>

@endsection
