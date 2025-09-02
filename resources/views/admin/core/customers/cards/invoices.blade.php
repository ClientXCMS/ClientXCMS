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
    return [$k => __('global.states.'.$v)];
})->toArray();
?>
<div class="card">
    <div class="flex justify-between">

        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">

            {{ __($translatePrefix . '.show.invoices') }}</h2>
        <div>
            @if (!empty($invoices_filters))
                <div class="mr-1 hs-dropdown relative inline-block md:[--placement:bottom-right]" data-hs-dropdown-auto-close="inside">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                        <svg class="flex-shrink-0 w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
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
                                @php(is_array($label) ? $label = $label[0] : $label)
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

    <div class="py-1 px-4 mx-auto">
    {{ $invoices->links('admin.shared.layouts.pagination') }}
    </div>

</div>
