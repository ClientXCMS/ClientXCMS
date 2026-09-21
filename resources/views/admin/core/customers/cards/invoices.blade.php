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
$invoices_filters = collect(\App\Models\Billing\Invoice::FILTERS)->mapWithKeys(function ($k, $v) {
    return [$k => __('global.states.' . $v)];
})->toArray();
?>
<div class="card">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ __($translatePrefix . '.show.invoices') }}
        </h2>
        <div class="flex flex-wrap items-center justify-end gap-2">
            @if (staff_has_permission('admin.manage_invoices'))
            <a href="{{ route('admin.invoices.create') }}?customer_id={{ $item->id }}" class="btn btn-primary inline-flex items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                {{ __($translatePrefix . '.show.create_invoice') }}
            </a>
            @endif
            @if(staff_has_permission('admin.export_invoices'))
            <div class="flex justify-end">
                <button type="button" class="btn btn-primary" data-hs-overlay="#customer-invoice-export-overlay">
                    <i class="bi bi-download mr-2"></i> {{ __('client.invoices.export.button') }}
                </button>
            </div>
            @endif
            @if (!empty($invoices_filters))
            <div class="mr-1 hs-dropdown relative inline-block md:[--placement:bottom-right]" data-hs-dropdown-auto-close="inside">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                    <svg class="flex-shrink-0 w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18" />
                        <path d="M7 12h10" />
                        <path d="M10 18h4" />
                    </svg>
                    {{ __('global.filter') }}
                    @if (count($checkedFilters) > 0 && collect($invoices_filters)->keys()->intersect($checkedFilters)->isNotEmpty())
                    <span class="ps-2 text-xs font-semibold text-blue-600 border-s border-gray-200 dark:border-gray-700 dark:text-blue-500">
                        {{ count($checkedFilters) }}
                    </span>
                    @endif
                </button>
                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden mt-2 divide-y divide-gray-200 min-w-[12rem] z-10 bg-white shadow-md rounded-lg mt-2 dark:divide-gray-700 dark:bg-gray-800 dark:border dark:border-gray-700" aria-labelledby="filter-items">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($invoices_filters as $current => $label)
                        @php
                        $label = is_array($label) ? $label[0] : $label;
                        @endphp
                        <label for="filter-{{ $current }}" class="flex py-2.5 px-3">
                            <input id="filter-{{ $current }}" data-key="status" value="{{ $current }}" type="checkbox" class="filter-checkbox shrink-0 mt-0.5 border-gray-300 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-600 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" @if (in_array($current, $checkedFilters)) checked @endif>
                            <span class="ms-3 text-sm text-gray-800 dark:text-gray-200">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
    <div class="border rounded-lg overflow-x-auto dark:border-gray-700" tabindex="0">

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
                                {{ __('global.status') }}
                            </span>
                        </div>
                    </th>

                    <th scope="col" class="px-6 py-3 text-start">
                        <div class="flex items-center gap-x-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                {{ __('client.invoices.due_date') }}
                            </span>
                        </div>
                    </th>

                    <th scope="col" class="px-6 py-3 text-start">
                        <div class="flex items-center gap-x-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                {{ __('client.invoices.invoice_date') }}
                            </span>
                        </div>
                    </th>

                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @if (count($invoices) == 0)
                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                {{ __('global.no_results') }}
                            </p>
                        </div>
                    </td>
                    @endif
                    @foreach($invoices as $invoice)
                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <td class="h-px w-px whitespace-nowrap">
                        <span class="block px-6 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                <a href="{{ route('admin.invoices.show', ['invoice' => $invoice]) }}">
                                    {{ formatted_price($invoice->total, $invoice->currency) }}</span>
                            </a>
                        </span>
                    </td>
                    <td class="h-px w-px whitespace-nowrap">
                        <x-badge-state state="{{ $invoice->status }}"></x-badge-state>
                    </td>
                    <td class="h-px w-px whitespace-nowrap">
                        <span class="block px-6 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->due_date->format('d/m/y') }}</span>
                        </span>
                    </td>
                    <td class="h-px w-px whitespace-nowrap">
                        <span class="block px-6 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->created_at->format('d/m/y') }}</span>
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

    @if(staff_has_permission('admin.export_invoices'))
    @php
    $exportInvoiceStatuses = $invoices_filters + [\App\Models\Billing\Invoice::STATUS_DRAFT => __('global.states.draft')];
    $selectedExportStatuses = old('status', $invoiceFilters['status'] ?? ['paid']);
    @endphp
    <div id="customer-invoice-export-overlay"
        class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden"
        tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">{{ __('client.invoices.export.title') }}</h3>
            <button type="button"
                class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                data-hs-overlay="#customer-invoice-export-overlay">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route('admin.customers.invoices.export', $item) }}">
                @include('admin/shared/flatpickr', [
                'name' => 'date_from',
                'label' => __('client.invoices.filters.date_from'),
                'value' => old('date_from', $invoiceFilters['date_from'] ?? \Carbon\Carbon::now()->subMonth()->format('Y-m-d')),
                'attributes' => ['autocomplete' => 'off'],
                ])
                @include('admin/shared/flatpickr', [
                'name' => 'date_to',
                'label' => __('client.invoices.filters.date_to'),
                'value' => old('date_to', $invoiceFilters['date_to'] ?? \Carbon\Carbon::now()->format('Y-m-d')),
                'attributes' => ['autocomplete' => 'off'],
                ])
                @include('admin/shared/search-select-multiple', [
                'name' => 'status[]',
                'label' => __('global.status'),
                'options' => $exportInvoiceStatuses,
                'value' => $selectedExportStatuses,
                ])
                @if($invoiceCurrencies->count() > 1)
                @include('admin/shared.select', [
                'name' => 'currency',
                'label' => __('client.invoices.filters.currency'),
                'options' => $invoiceCurrencies->mapWithKeys(fn ($currency) => [$currency => $currency])->all(),
                'value' => old('currency', $invoiceFilters['currency'] ?? $invoiceCurrencies->first()),
                ])
                @elseif($invoiceCurrencies->isNotEmpty())
                <input type="hidden" name="currency" value="{{ $invoiceCurrencies->first() }}">
                @endif
                @csrf
                @include('admin/shared.select', [
                'name' => 'format',
                'label' => __('client.invoices.export.format'),
                'options' => \App\Services\InvoiceExporterService::getAvailableFormats(),
                'value' => old('format', 'csv'),
                ])
                <button class="btn btn-primary mt-2 w-full" type="submit">{{ __('client.invoices.export.button') }}</button>
            </form>
        </div>
    </div>
    @endif

    <div class="py-1 px-4 mx-auto">
        {{ $invoices->links('admin.shared.layouts.pagination') }}
    </div>

</div>