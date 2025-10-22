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
    Session::flash('error', collect($errors->all())->map(function ($error) {
    return $error;
    })->implode('<br>'));
    @endphp
    @endif
    @include('admin/shared/alerts')
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
                            {{ __($translatePrefix .'.show.tabs.show') }}
                        </button>
                        @if (staff_has_permission('admin.manage_invoices'))

                        <button type="button"
                            class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                            id="tabs-with-underline-item-1" data-hs-tab="#tabs-with-underline-1"
                            aria-controls="tabs-with-underline-1" role="tab">
                            {{ __($translatePrefix .'.show.tabs.edit') }}
                        </button>
                        <button type="button"
                            class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                            id="tabs-with-underline-item-2" data-hs-tab="#tabs-with-underline-2"
                            aria-controls="tabs-with-underline-2" role="tab">
                            {{ __($translatePrefix .'.show.tabs.billing') }}
                        </button>
                        @if (!$invoice->isDraft())
                        <button type="button"
                            class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                            id="tabs-with-underline-item-3" data-hs-tab="#tabs-with-underline-3"
                            aria-controls="tabs-with-underline-3" role="tab">
                            {{ __($translatePrefix .'.show.fulfillment.title') }}
                        </button>
                        @endif
                        @endif
                        <button type="button"
                            class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 md:py-4 px-2 inline-flex items-center gap-x-2 border-l-2 md:border-l-0 md:border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                            id="tabs-with-underline-item-logs" data-hs-tab="#tabs-with-underline-logs"
                            aria-controls="tabs-with-underline-logs" role="tab">
                            {{ __($translatePrefix .'.show.tabs.logs') }}
                        </button>
                    </nav>
                </div>


                <div class="mt-3">
                    <div id="tabs-with-underline-0" role="tabpanel" aria-labelledby="tabs-with-underline-item-0">
                        @include('admin/core/invoices/tabs/show')
                    </div>
                    @if (staff_has_permission('admin.manage_invoices'))
                        <div id="tabs-with-underline-1" class="hidden" role="tabpanel" aria-labelledby="tabs-with-underline-item-1">
                            @include('admin/core/invoices/tabs/edit')
                        </div>
                        <div id="tabs-with-underline-2" class="hidden" role="tabpanel" aria-labelledby="tabs-with-underline-item-2">
                            @include('admin/core/invoices/tabs/billing')
                        </div>
                        @if (!$invoice->isDraft())
                            <div id="tabs-with-underline-3" class="hidden" role="tabpanel" aria-labelledby="tabs-with-underline-item-3">
                                @include('admin/core/invoices/tabs/fulfillment')
                            </div>
                        @endif
                    @endif
                    <div id="tabs-with-underline-logs" class="hidden" role="tabpanel" aria-labelledby="tabs-with-underline-item-logs">
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
                            <input type="text" readonly class="input-text" id="invoice_url" value="{{ route('front.invoices.show', ['invoice' => $invoice->uuid]) }}">
                            <button type="button" data-clipboard-target="#invoice_url" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                </svg>
                                <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    @include('admin/shared/input', ['name' => 'uuid', 'label' => "UUID", 'value' => $invoice->uuid, 'readonly' => true])
                    <div class="grid grid-cols-2 gap-2">
                        <div>

                            @include('admin/shared/input', ['name' => 'id', 'label' => "ID", 'value' => $invoice->id, 'readonly' => true])
                        </div>
                        <div>
                            @include('admin/shared/input', ['name' => 'created_at', 'label' => __('global.created'), 'value' => $invoice->created_at->format('d/m/y H:i'), 'readonly' => true])
                        </div>
                    </div>
                    @if (!$invoice->isDraft())

                    <button class="btn btn-secondary text-left mt-2" type="button" data-hs-overlay="#metadata-overlay">
                        <i class="bi bi-database mr-2"></i>
                        {{ __('admin.metadata.title') }}
                    </button>
                    <a href="{{ route($routePath . '.notify', ['invoice' => $invoice]) }}" class="btn btn-info mt-2">
                        <i class="bi bi-envelope-check-fill mr-3"></i>
                        {{ __($translatePrefix . '.notify') }}</a>
                    @endif
                </form>

            </div>
            @if ($customer && $customer->paymentMethods()->isNotEmpty() && $invoice->status == $invoice::STATUS_PENDING)
            <div class="card card-body mt-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.payinvoicetitle') }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translatePrefix . '.payinvoicedescription') }}</p>
                <form method="POST" action="{{ route($routePath . '.pay', ['invoice' => $invoice]) }}">
                    @csrf
                    @include('admin/shared/select', ['name' => 'source', 'label' => __('client.payment-methods.paymentmethod'), 'options' => $customer->getPaymentMethodsArray(), 'value' => $invoice->paymethod])
                    <button class="btn btn-secondary mt-2">
                        <i class="bi bi-credit-card-fill mr-3"></i>
                        {{ __($translatePrefix. '.payinvoicebtn') }}</button>
                </form>
            </div>
            @endif


            @if ($invoice->isDraft() && staff_has_permission('admin.create_invoices'))
            <form method="POST" action="{{ route($routePath . '.validate', ['invoice' => $invoice]) }}">
                @csrf
                <button class="btn btn-secondary w-full mt-2">
                    <i class="bi bi-check-circle-fill text-success"></i>

                    {{ __($translatePrefix . '.draft.validatebtn') }}</button>
            </form>
            @elseif ($invoice->status == \App\Models\Billing\Invoice::STATUS_PENDING && staff_has_permission('admin.create_invoices'))

            <form method="POST" action="{{ route($routePath . '.edit', ['invoice' => $invoice]) }}">
                @csrf
                <button class="btn btn-secondary w-full mt-2">
                    <i class="bi bi-pen"></i>

                    {{ __($translatePrefix . '.edit') }}</button>
            </form>
            @endif
        </div>
        @include('admin/metadata/overlay', ['item' => $invoice, 'items' => collect([$invoice])->merge($invoice->items)])
        @if ($invoice->isDraft())
        @include('admin/core/invoices/draftoverlay', ['invoice' => $invoice])
        @endif
    </div>
    @endsection
