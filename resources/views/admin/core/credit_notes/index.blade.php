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
@section('title', __('admin.credit_notes.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4">
            <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                        <i class="bi bi-file-earmark-diff text-black dark:text-white"></i>
                    </div>
                    <div class="grow">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('admin.credit_notes.kpi.count') }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">{{ $summaryTotals['count'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                        <i class="bi bi-cash-stack text-black dark:text-white"></i>
                    </div>
                    <div class="grow">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('admin.credit_notes.kpi.amount') }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
                            @if ($summaryRows->count() === 0)
                                {{ formatted_price(0, currency()) }}
                            @elseif ($summaryRows->count() === 1)
                                {{ formatted_price($summaryRows->first()->amount, $summaryRows->first()->currency) }}
                            @else
                                {{ __('admin.credit_notes.multi_currency') }}
                            @endif
                        </h3>
                    </div>
                </div>
            </div>
            <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                        <i class="bi bi-percent text-black dark:text-white"></i>
                    </div>
                    <div class="grow">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('admin.credit_notes.kpi.tax') }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
                            @if ($summaryRows->count() === 0)
                                {{ formatted_price(0, currency()) }}
                            @elseif ($summaryRows->count() === 1)
                                {{ formatted_price($summaryRows->first()->tax, $summaryRows->first()->currency) }}
                            @else
                                {{ __('admin.credit_notes.multi_currency') }}
                            @endif
                        </h3>
                    </div>
                </div>
            </div>
            <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                        <i class="bi bi-calculator text-black dark:text-white"></i>
                    </div>
                    <div class="grow">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('admin.credit_notes.kpi.total') }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
                            @if ($summaryRows->count() === 0)
                                {{ formatted_price(0, currency()) }}
                            @elseif ($summaryRows->count() === 1)
                                {{ formatted_price($summaryRows->first()->total, $summaryRows->first()->currency) }}
                            @else
                                {{ __('admin.credit_notes.multi_currency') }}
                            @endif
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        @if ($summaryRows->count() > 1)
            <div class="card">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('admin.credit_notes.kpi.by_currency') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach ($summaryRows as $row)
                        <div class="border rounded-lg p-3 dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $row->currency }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.credit_notes.kpi.count') }}: {{ $row->count }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.credit_notes.kpi.total') }}: {{ formatted_price($row->total, $row->currency) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('admin.credit_notes.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('admin.credit_notes.subheading') }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            
                            @include('admin/shared/mass_actions/header', [
                                'searchFields' => $searchFields,
                                'search' => $search,
                                'searchField' => $searchField,
                                'filters' => $filters,
                                'checkedFilters' => $checkedFilters,
                                'filterField' => $filterField,
                            ])    
                            <a class="btn btn-secondary text-sm w-1/2 sm:w-auto"
                                    href="{{ route('admin.invoices.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    {{ __('admin.invoices.title') }}
                                </a>
                            </div>
                        </div>
                        @if (staff_has_permission('admin.manage_invoices'))
                            <div class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800/60 dark:bg-amber-900/20 dark:text-amber-300">
                                <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
                                <span>{{ __('admin.credit_notes.delete_balance_warning') }}</span>
                            </div>
                        @endif
                        </div>
                        <div class="border rounded-lg overflow-hidden dark:border-gray-700 mt-4">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('client.invoices.credit_note_number') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.customer') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('admin.credit_notes.original_invoice') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('admin.credit_notes.amount_ht') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('admin.credit_notes.tax') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('store.total') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.currency') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.date') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('admin.credit_notes.issued_by') }}</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($items as $creditNote)
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $creditNote->credit_note_number }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                @if ($creditNote->customer)
                                                    <a class="text-sm text-blue-600 dark:text-blue-500 hover:underline" href="{{ route('admin.customers.show', ['customer' => $creditNote->customer]) }}">
                                                        {{ $creditNote->customer->excerptFullName() }}
                                                    </a>
                                                @else
                                                    <span class="text-sm italic text-gray-400 dark:text-gray-600">({{ __('global.deleted') }})</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                @if ($creditNote->invoice)
                                                    <a class="text-sm text-blue-600 dark:text-blue-500 hover:underline" href="{{ route('admin.invoices.show', ['invoice' => $creditNote->invoice]) }}">
                                                        #{{ $creditNote->invoice->invoice_number }}
                                                    </a>
                                                @else
                                                    <span class="text-sm text-gray-400">-</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($creditNote->amount, $creditNote->currency) }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($creditNote->tax, $creditNote->currency) }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($creditNote->amount + $creditNote->tax, $creditNote->currency) }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $creditNote->currency }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $creditNote->created_at->format('d/m/y') }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $creditNote->admin?->username ?? '-' }}</span>
                                            </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                            <a href="{{ route('admin.credit_notes.pdf', $creditNote) }}" target="_blank">
                                                <span class="py-1.5">
                                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                        <i class="bi bi-eye-fill"></i>
                                                        {{ __('global.show') }}
                                                    </span>
                                                </span>
                                            </a>
                                            <a href="{{ route('admin.credit_notes.download', $creditNote) }}">
                                                <span class="py-1.5">
                                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                        <i class="bi bi-download"></i>
                                                        {{ __('global.download') }}
                                                    </span>
                                                </span>
                                            </a>
                                            @if (staff_has_permission('admin.manage_invoices'))
                                                <form action="{{ route('admin.credit_notes.destroy', $creditNote) }}" method="POST" class="inline confirmation-popup">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button>
                                                        <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                            <i class="bi bi-trash"></i>
                                                            {{ __('global.delete') }}
                                                        </span>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td colspan="10" class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                <p class="text-sm text-gray-800 dark:text-gray-400">{{ __('global.no_results') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
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
    </div>
@endsection
