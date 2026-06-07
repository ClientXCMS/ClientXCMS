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
@section('title', __($translatePrefix . '.show.title', ['id' => $item->identifier()]))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/invoicedraft.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

        @if ($invoice->isDraft() && !empty($errors))
            @php
                Session::flash(
                    'error',
                    collect($errors->all())
                        ->map(function ($error) {
                            return $error;
                        })
                        ->implode('<br>'),
                );
            @endphp
        @endif
        @include('admin/shared/alerts')
        <div class="card mb-4 card-body">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white text-xl font-bold">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            {{ __('global.invoice') }} #{{ $invoice->invoice_number ?? $invoice->id }}
                            <x-badge-state state="{{ $invoice->status }}"></x-badge-state>
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if ($invoice->customer)
                                <a href="{{ route('admin.customers.show', ['customer' => $invoice->customer]) }}" class="hover:underline">
                                    <i class="bi bi-person mr-1"></i>{{ $invoice->customer->fullname }}
                                </a>
                            @else
                                {{ __('admin.customers.no_customer') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if (
                    !$invoice->isDraft() ||
                        staff_has_permission('admin.create_invoices') ||
                        (staff_has_permission('admin.manage_invoices') && $invoice->canDelete()))
                    <div class="flex items-center gap-2">
                        <div class="hs-dropdown relative inline-flex">
                            <button id="hs-dropdown-with-title" type="button" class="hs-dropdown-toggle btn btn-secondary"
                                aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                {{ __('global.actions') }}
                                <i class="bi bi-caret-down-fill hs-dropdown-open:rotate-180 ml-1"></i>
                            </button>

                            <div style="z-index: 10000"
                                class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 divide-y divide-gray-200 dark:bg-gray-800 dark:border dark:border-neutral-700 dark:divide-neutral-700"
                                role="menu">
                                <div class="p-1 space-y-0.5">
                                    @if (!$invoice->isDraft())
                                        <button class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 text-left cursor-pointer" type="button"
                                            data-hs-overlay="#metadata-overlay">
                                            <i class="bi bi-database mr-2"></i>
                                            {{ __('admin.metadata.title') }}
                                        </button>
                                        <form method="POST"
                                            action="{{ route($routePath . '.regenerate_pdf', ['invoice' => $invoice]) }}"
                                            class="contents">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 text-left">
                                                <i class="bi bi-file-earmark-pdf-fill mr-2"></i>
                                                {{ __($translatePrefix . '.regenerate_pdf') }}
                                            </button>
                                        </form>
                                        <a href="{{ route($routePath . '.notify', ['invoice' => $invoice]) }}"
                                            class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700">
                                            <i class="bi bi-envelope-check-fill mr-2"></i>
                                            {{ __($translatePrefix . '.notify') }}
                                        </a>
                                        @if (staff_has_permission('admin.manage_invoices'))
                                            <button class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 text-left cursor-pointer" type="button"
                                                data-hs-overlay="#credit-note-overlay">
                                                <i class="bi bi-file-earmark-diff mr-2"></i>
                                                {{ __('admin.credit_notes.issue_credit_note') }}
                                            </button>
                                        @endif
                                        <a href="{{ route($routePath . '.pdf', ['invoice' => $invoice]) }}" target="_blank">
                                            <button type="button" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700">
                                                <i class="bi bi-file-earmark-pdf-fill mr-2"></i>
                                                {{ __('client.invoices.download') }}
                                            </button>
                                        </a>
                                    @endif

                                    @if ($invoice->isDraft() && staff_has_permission('admin.create_invoices'))
                                        <form method="POST" action="{{ route($routePath . '.validate', ['invoice' => $invoice]) }}" class="contents">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 text-left">
                                                <i class="bi bi-check-circle-fill text-success mr-2"></i>
                                                {{ __($translatePrefix . '.draft.validatebtn') }}
                                            </button>
                                        </form>
                                    @elseif ($invoice->status == \App\Models\Billing\Invoice::STATUS_PENDING && staff_has_permission('admin.create_invoices'))
                                        <form method="POST" action="{{ route($routePath . '.edit', ['invoice' => $invoice]) }}" class="contents">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 text-left">
                                                <i class="bi bi-pen mr-2"></i>
                                                {{ __($translatePrefix . '.edit') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if (staff_has_permission('admin.manage_invoices') && $invoice->canDelete())
                                    <div class="p-1 space-y-0.5">
                                        <form method="POST" action="{{ route($routePath . '.destroy', ['invoice' => $invoice]) }}" onsubmit="return confirmation();" class="contents">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-700 text-left">
                                                <i class="bi bi-trash mr-2"></i>
                                                {{ __('global.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="md:w-2/3">
                <div class="card">
                    @csrf
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2" role="tablist">
                            <button type="button"
                                class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none active"
                                id="tabs-with-underline-item-0" data-hs-tab="#tabs-with-underline-0"
                                aria-controls="tabs-with-underline-0" role="tab">
                                {{ __($translatePrefix . '.show.tabs.show') }}
                            </button>
                            @if (staff_has_permission('admin.manage_invoices'))

                                <button type="button"
                                    class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                                    id="tabs-with-underline-item-1" data-hs-tab="#tabs-with-underline-1"
                                    aria-controls="tabs-with-underline-1" role="tab">
                                    {{ __($translatePrefix . '.show.tabs.edit') }}
                                </button>
                                <button type="button"
                                    class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                                    id="tabs-with-underline-item-2" data-hs-tab="#tabs-with-underline-2"
                                    aria-controls="tabs-with-underline-2" role="tab">
                                    {{ __($translatePrefix . '.show.tabs.billing') }}
                                </button>
                                @if (!$invoice->isDraft())
                                    <button type="button"
                                        class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                                        id="tabs-with-underline-item-3" data-hs-tab="#tabs-with-underline-3"
                                        aria-controls="tabs-with-underline-3" role="tab">
                                        {{ __($translatePrefix . '.show.fulfillment.title') }}
                                    </button>
                                @endif
                            @endif
                            <button type="button"
                                class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                                id="tabs-with-underline-item-logs" data-hs-tab="#tabs-with-underline-logs"
                                aria-controls="tabs-with-underline-logs" role="tab">
                                {{ __($translatePrefix . '.show.tabs.logs') }}
                            </button>
                        </nav>
                    </div>


                    <div class="mt-3">
                        <div id="tabs-with-underline-0" role="tabpanel" aria-labelledby="tabs-with-underline-item-0">
                            @include('admin/core/invoices/tabs/show')
                        </div>
                        @if (staff_has_permission('admin.manage_invoices'))
                            <div id="tabs-with-underline-1" class="hidden" role="tabpanel"
                                aria-labelledby="tabs-with-underline-item-1">
                                @include('admin/core/invoices/tabs/edit')
                            </div>
                            <div id="tabs-with-underline-2" class="hidden" role="tabpanel"
                                aria-labelledby="tabs-with-underline-item-2">
                                @include('admin/core/invoices/tabs/billing')
                            </div>
                            @if (!$invoice->isDraft())
                                <div id="tabs-with-underline-3" class="hidden" role="tabpanel"
                                    aria-labelledby="tabs-with-underline-item-3">
                                    @include('admin/core/invoices/tabs/fulfillment')
                                </div>
                            @endif
                        @endif
                        <div id="tabs-with-underline-logs" class="hidden" role="tabpanel"
                            aria-labelledby="tabs-with-underline-item-logs">
                            @include('admin/core/invoices/tabs/logs')
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:w-1/3">
                <div class="card">
                    <form method="POST" action="{{ route($routePath . '.update', ['invoice' => $invoice]) }}">
                        @csrf
                        @method('PUT')

                        @if (!$invoice->isDraft())
                            <div>
                                <div class="flex rounded-lg shadow-sm mt-2">
                                    <input type="text" readonly class="input-text" id="invoice_url"
                                        value="{{ route('front.invoices.show', ['invoice' => $invoice->uuid]) }}">
                                    <button type="button" data-clipboard-target="#invoice_url" data-clipboard-action="copy"
                                        data-clipboard-success-text="Copied"
                                        class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                        <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                                            <path
                                                d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                        </svg>
                                        <svg class="js-clipboard-success hidden w-4 h-4 text-white"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @include('admin/shared/input', [
                            'name' => 'uuid',
                            'label' => 'UUID',
                            'value' => $invoice->uuid,
                            'readonly' => true,
                        ])
                        <div class="grid grid-cols-2 gap-2">
                            <div>

                                @include('admin/shared/input', [
                                    'name' => 'id',
                                    'label' => 'ID',
                                    'value' => $invoice->id,
                                    'readonly' => true,
                                ])
                            </div>
                            <div>
                                @include('admin/shared/input', [
                                    'name' => 'created_at',
                                    'label' => __('global.created'),
                                    'value' => $invoice->created_at->format('d/m/y H:i'),
                                    'readonly' => true,
                                ])
                            </div>
                        </div>
                    </form>

                </div>
                @if ($customer && $customer->paymentMethods()->isNotEmpty() && $invoice->status == $invoice::STATUS_PENDING)
                    <div class="card card-body mt-3">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __($translatePrefix . '.payinvoicetitle') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __($translatePrefix . '.payinvoicedescription') }}</p>
                        <form method="POST" action="{{ route($routePath . '.pay', ['invoice' => $invoice]) }}">
                            @csrf
                            @include('admin/shared/select', [
                                'name' => 'source',
                                'label' => __('client.payment-methods.paymentmethod'),
                                'options' => $customer->getPaymentMethodsArray(),
                                'value' => $invoice->paymethod,
                            ])
                            <button class="btn btn-secondary mt-2">
                                <i class="bi bi-credit-card-fill mr-3"></i>
                                {{ __($translatePrefix . '.payinvoicebtn') }}</button>
                        </form>
                    </div>
                @endif

                @stack('invoice-sidebar')
            </div>
            @include('admin/metadata/overlay', [
                'item' => $invoice,
                'items' => collect([$invoice])->merge($invoice->items),
            ])
            @if ($invoice->isDraft())
                @include('admin/core/invoices/draftoverlay', ['invoice' => $invoice])
            @endif
            @if (staff_has_permission('admin.manage_invoices') && !$invoice->isDraft())
                <div id="credit-note-overlay"
                    class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-sm w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700"
                    tabindex="-1">
                    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                        <h3 class="font-bold text-gray-800 dark:text-white">
                            {{ __('admin.credit_notes.issue_credit_note') }}
                        </h3>
                        <button type="button"
                            class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                            data-hs-overlay="#credit-note-overlay">
                            <span class="sr-only">{{ __('global.closemodal') }}</span>
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <form method="POST"
                            action="{{ route('admin.customers.credit_notes.store', ['customer' => $invoice->customer]) }}">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                            <div class="mb-4">
                                <label
                                    class="block text-sm font-medium mb-2 dark:text-white">{{ __('admin.credit_notes.original_invoice') }}</label>
                                <input type="text"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm bg-gray-100 dark:bg-slate-700 dark:border-gray-600 dark:text-gray-400"
                                    readonly
                                    value="#{{ $invoice->invoice_number }} ({{ formatted_price($invoice->total, $invoice->currency) }})">
                            </div>

                            <div class="mb-4">
                                @include('admin/shared/input', [
                                    'name' => 'amount',
                                    'label' => __('admin.credit_notes.amount'),
                                    'type' => 'number',
                                    'value' => old('amount', $invoice->total),
                                    'step' => '0.01',
                                    'required' => true,
                                ])
                            </div>

                            <div class="mb-4">
                                @include('admin/shared/textarea', [
                                    'name' => 'reason',
                                    'label' => __('admin.credit_notes.reason'),
                                    'value' => old('reason'),
                                    'required' => false,
                                ])
                            </div>

                            <button type="submit" class="w-full btn btn-indigo-600">
                                {{ __('global.create') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endsection
