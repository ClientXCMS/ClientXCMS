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
@section('title', __($translatePrefix . '.title'))
@section('scripts')
<script src="{{ Vite::asset('resources/themes/default/js/filter.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartData = @json($chartData);

        if (chartData.data.length > 0) {
            const ctx = document.getElementById('cancellationChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderWidth: 2,
                        borderColor: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151',
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((context.raw / total) * 100);
                                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')
    <div class="flex flex-col mb-8">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="card">
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __($translatePrefix . '.title') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __($translatePrefix . '.subheading') }}
                            </p>
                        </div>

                        <div class="flex">
                            <form>
                                <label for="hs-as-table-product-review-search" class="sr-only">{{ __('global.search') }}</label>
                                <div class="relative">
                                    <input type="text" value="{{ $search ?? '' }}" id="hs-as-table-product-review-search" name="q" class="py-2 px-3 ps-11 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="{{ __('global.search') }}">
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-4">
                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </form>
                            @if (!empty($filters))
                            <div class="ml-4 hs-dropdown relative inline-block [--placement:bottom-right]" data-hs-dropdown-auto-close="inside">
                                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                    <svg class="flex-shrink-0 w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18" />
                                        <path d="M7 12h10" />
                                        <path d="M10 18h4" />
                                    </svg>
                                    {{ __('global.filter') }}
                                    @if (!empty($checkedFilters))
                                    <span class="ps-2 text-xs font-semibold text-blue-600 border-s border-gray-200 dark:border-gray-700 dark:text-blue-500">
                                        {{ count($checkedFilters) }}
                                    </span>
                                    @endif
                                </button>
                                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden mt-2 divide-y divide-gray-200 min-w-[12rem] z-10 bg-white shadow-md rounded-lg mt-2 dark:divide-gray-700 dark:bg-gray-800 dark:border dark:border-gray-700" aria-labelledby="filter-items">
                                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($filters as $current => $label)
                                        <label for="filter-{{ $current }}" class="flex py-2.5 px-3">
                                            <input id="filter-{{ $current }}" value="{{ $current }}" type="checkbox" class="filter-checkbox shrink-0 mt-0.5 border-gray-300 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-600 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" @if (in_array($current, $checkedFilters)) checked @endif>
                                            <span class="ms-3 text-sm text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif

                            <a class="btn btn-primary text-sm ml-2" href="{{ route($routePath . '.create') }}">
                                {{ __('admin.create') }}
                            </a>
                        </div>
                    </div>
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">#</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.reason') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.status') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.created') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @if (count($items) == 0)
                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                                {{ __('global.no_results') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @foreach($items as $item)
                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                    <td class="h-px w-px whitespace-nowrap">
                                        <span class="block px-6 py-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                                        </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <span class="block px-6 py-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ \Str::limit($item->reason, 50) }}</span>
                                        </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        @if ($item->status == 'active')
                                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                            {{ __('global.states.active') }}
                                        </span>
                                        @elseif ($item->status == 'hidden')
                                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full dark:bg-yellow-500/10 dark:text-yellow-500">
                                            {{ __('global.states.hidden') }}
                                        </span>
                                        @else
                                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-500/10 dark:text-gray-500">
                                            {{ __('global.states.unreferenced') }}
                                        </span>
                                        @endif
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <span class="block px-6 py-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at != null ? $item->created_at->format('d/m/y') : 'None' }}</span>
                                        </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <a href="{{ route($routePath . '.show', ['cancellation_reason' => $item]) }}">
                                            <span class="py-1.5">
                                                <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                    <i class="bi bi-eye-fill"></i>
                                                    {{ __('global.show') }}
                                                </span>
                                            </span>
                                        </a>
                                        <form method="POST" action="{{ route($routePath . '.destroy', ['cancellation_reason' => $item]) }}" class="inline confirmation-popup">
                                            @method('DELETE')
                                            @csrf
                                            <button>
                                                <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                    <i class="bi bi-trash"></i>
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
                        {{ $items->links('admin.shared.layouts.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">
            <i class="bi bi-bar-chart mr-2"></i>
            {{ __($translatePrefix . '.analytics.title') }}
        </h2>
    </div>

    <div class="card mb-6">
        <form method="GET" action="{{ route($routePath . '.index') }}" class="p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium mb-2 dark:text-white">
                        {{ __('admin.dashboard.earn.start_date') }}
                    </label>
                    <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                        class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium mb-2 dark:text-white">
                        {{ __('admin.dashboard.earn.end_date') }}
                    </label>
                    <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                        class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel mr-2"></i>
                    {{ __('global.filter') }}
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="bi bi-x-circle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($translatePrefix . '.analytics.total_cancellations') }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $totalCancellations }}</p>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="bi bi-list-ul text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($translatePrefix . '.analytics.reasons_count') }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ count($chartData['labels']) }}</p>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="bi bi-calendar-range text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($translatePrefix . '.analytics.period') }}</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                {{ __($translatePrefix . '.analytics.distribution') }}
            </h3>
            @if(count($chartData['data']) > 0)
            <div class="h-64">
                <canvas id="cancellationChart"></canvas>
            </div>
            @else
            <div class="flex flex-col items-center justify-center h-48 text-gray-500 dark:text-gray-400">
                <i class="bi bi-pie-chart text-4xl mb-4"></i>
                <p>{{ __($translatePrefix . '.analytics.no_data') }}</p>
            </div>
            @endif
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                {{ __($translatePrefix . '.analytics.breakdown') }}
            </h3>
            @if(count($chartData['data']) > 0)
            <div class="space-y-3">
                @foreach($chartData['labels'] as $index => $label)
                @php
                $count = $chartData['data'][$index];
                $percentage = $totalCancellations > 0 ? round(($count / $totalCancellations) * 100, 1) : 0;
                $color = $chartData['colors'][$index];
                @endphp
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $count }} ({{ $percentage }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="h-2 rounded-full" style="width: {{ $percentage }}%; background-color: {{ $color }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center h-48 text-gray-500 dark:text-gray-400">
                <i class="bi bi-bar-chart text-4xl mb-4"></i>
                <p>{{ __($translatePrefix . '.analytics.no_data') }}</p>
            </div>
            @endif
        </div>
    </div>

    @if(count($cancelledServices) > 0)
    <div class="card">
        <div class="card-heading">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                {{ __($translatePrefix . '.analytics.cancelled_services') }}
            </h3>
        </div>
        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th scope="col" class="px-6 py-3 text-start">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.service') }}</span>
                        </th>
                        <th scope="col" class="px-6 py-3 text-start">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.customer') }}</span>
                        </th>
                        <th scope="col" class="px-6 py-3 text-start">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.reason') }}</span>
                        </th>
                        <th scope="col" class="px-6 py-3 text-start">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.analytics.cancelled_at') }}</span>
                        </th>
                        <th scope="col" class="px-6 py-3 text-start">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($cancelledServices as $service)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-800 dark:text-gray-200">{{ \Str::limit($service->name, 30) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $service->product?->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($service->customer)
                            <a href="{{ route('admin.customers.show', $service->customer) }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                {{ $service->customer->FullName }}
                            </a>
                            @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $reasons->get($service->cancelled_reason)?->reason ?? __('global.unknown') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $service->cancelled_at?->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.services.show', $service) }}" class="py-1 px-2 inline-flex items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm hover:bg-gray-50 text-sm dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white">
                                <i class="bi bi-eye-fill"></i>
                                {{ __('global.show') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection