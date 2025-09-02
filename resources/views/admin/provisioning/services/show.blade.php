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
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/metadata.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/themes/default/js/popupwindow.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/serviceshow.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>

@endsection
@section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')
        <div class="flex flex-col md:flex-row gap-4">
            <div class="md:w-3/4">
                @if (!isset($intab) || !$intab)
                    <form method="POST" class="card" action="{{ route($routePath . '.update', ['service' => $item]) }}">
                        <div class="card-heading">
                            <div>

                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                    <x-badge-state state="{{ $item->status }}"></x-badge-state>

                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y'), 'owner' => $item->customer ? $item->customer->fullName : __('global.deleted')]) }}
                                </p>
                            </div>
                            @if (staff_has_permission('admin.manage_services'))

                                <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                    <button class="btn btn-primary">
                                        {{ __('admin.updatedetails') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                        @method('PUT')
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex flex-col">
                                @include('/admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => $item->name])
                            </div>
                            <div class="flex flex-col">
                                @if ($item->isOnetime())
                                    @include('/admin/shared/input', ['name' => 'expires_at', 'label' => __('global.expiration'), 'value' => __('recurring.onetime'), 'disabled' => true])
                                @else
                                    @include('/admin/shared/flatpickr', ['name' => 'expires_at', 'label' => __('global.expiration'), 'value' => $item->expires_at ? $item->expires_at->format('Y-m-d H:i') : null, 'type' => 'datetime'])
                                @endif

                            </div>
                            <div>
                                <label for="billing" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.recurrences') }}</label>
                                <div class="relative mt-2">
                                    <select id="billing" name="billing" class="py-3 px-4 ps-9 pe-20 input-text">
                                        @foreach($item->allowedBillingsLabels() as $billing => $billingLabel)
                                            <option value="{{ $billing }}" @if($billing == $item->billing) selected @endif>{{ $billingLabel }}</option>
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

                            <div class="flex flex-col">
                                @include('/admin/shared/select', ['name' => 'type', 'label' => __('provisioning.admin.services.show.type'), 'options' => $types, 'value' => $item->type])
                                @include('/admin/shared/input', ['name' => 'max_renewals', 'label' => __('provisioning.admin.services.show.max_renewals'), 'value' => $item->max_renewals, 'type' => 'number', 'help' => __('admin.blanktonolimit')])
                                @include('/admin/shared/select', ['name' => 'status', 'label' => __('global.state'), 'options' => $item::getStatuses(), 'value' => $item->status])

                            </div>
                            <div class="flex flex-col">
                                @include('/admin/shared/select', ['name' => 'server_id', 'label' => __('global.server'), 'options' => $servers, 'value' => old('server_id', $item->server_id ?? 'none')])
                                @include('/admin/shared/textarea', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description), 'help' => __('provisioning.admin.services.show.description_help')])

                            </div>
                            <div class="flex flex-col">
                                @include('/admin/shared/select', ['name' => 'product_id', 'label' => __('global.product'), 'options' => $products, 'value' => old('product_id', $item->product_id ?? 'none')])
                                @include('/admin/shared/textarea', ['name' => 'notes', 'label' => __('provisioning.admin.services.show.notes'), 'value' => old('notes', $item->notes), 'help' => __('provisioning.admin.services.show.notes_help')])

                            </div>

                            <input type="hidden" name="customer_id" value="{{ $item->customer_id }}">

                        </div>
                        <div>
                                        <div class="-m-1.5 overflow-x-auto">

                            @include('admin/shared/pricing/table', ['fees' => false])
                                        </div>
                            @if ($pricing->related_type == 'service' && $item->product != null )
                                <button class="btn btn-secondary" name="resync" value="true"><i class="bi bi-gear mr-2"></i>{{ __($translatePrefix . '.resync') }}</button>
                                @endif
                        </div>
                        @if ($pricing->related_type == 'product')
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                {{ __('provisioning.admin.services.pricing_synchronized') }}
                            </p>
                            @else
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                {{ __('provisioning.admin.services.pricing_custom2') }}
                            </p>
                        @endif

                    </form>
                @endif

                <nav class="relative z-0 flex mb-3 border rounded-xl overflow-hidden dark:border-slate-700 flex-col md:flex-row" aria-label="Tabs" role="tablist">
                    @if (!empty($panel_html))
                        <button type="button" class="active hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400" id="service-title-panel" data-hs-tab="#service-tab-panel" aria-controls="service-tab-panel" role="tab">
                            <i class="bi bi-box"></i>
                            <span class="ml-2">{{ __($translatePrefix . '.show.panel.title') }}</span>
                        </button>
                    @endif

                    <button type="button" class="{{ empty($panel_html)  ? 'active ' : ''}}hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400" id="service-title-renewal" data-hs-tab="#service-tab-renewal" aria-controls="service-tab-renewal" role="tab">
                        <i class="bi bi-calendar2-check"></i>
                        <span class="ml-2">{{ __('provisioning.admin.services.renewals.btn') }}</span>
                    </button>
                    <button type="button" class="hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400" id="service-title-configoptions" data-hs-tab="#service-tab-configoptions" aria-controls="service-tab-configoptions" role="tab">
                        <i class="bi bi-cart-plus"></i>
                        <span class="ml-2">{{ __('provisioning.admin.services.configoptions.btn') }}</span>
                    </button>

                        <button type="button" class="hs-tab-active:border-b-blue-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-blue-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-blue-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400" id="service-title-upgrade" data-hs-tab="#service-tab-upgrade" aria-controls="service-tab-upgrade" role="tab">
                            <i class="bi bi-arrows-angle-expand"></i>
                            <span class="ml-2">{{ __('provisioning.admin.services.upgrade.btn') }}</span>
                        </button>
                </nav>
                <div id="service-tab-panel" class="{{ !empty($panel_html) ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="service-title-panel">
                    {!! $panel_html !!}
                </div>
                <div id="service-tab-renewal" class="{{ empty($panel_html) ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="service-title-renewal">
                    <div class="card card-body">
                        <h3 class="font-bold text-gray-800 dark:text-white mb-3">
                            {{ __($translatePrefix . '.renewals.title') }}
                        </h3>
                        <div>
                            @if (staff_has_permission('admin.show_invoices'))
                                <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                        <tr>

                                            <th scope="col" class="px-6 py-3 text-start">
                                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('client.services.renewals.period') }}
                    </span>
                                                </div>
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-start">
                                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.invoice') }}
                    </span>
                                                </div>
                                            </th>

                                            <th scope="col" class="px-6 py-3 text-start">
                                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('store.price') }}
                    </span>
                                                </div>
                                            </th>

                                            <th scope="col" class="px-6 py-3 text-start">
                                                <div class="flex items-center gap-x-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                      {{ __('client.services.renewals.date') }}
                                    </span>
                                                </div>
                                            </th>


                                            <th scope="col" class="px-6 py-3 text-end"></th>
                                        </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($renewals as $renewal)
                                            @if ($renewal->invoice == null)
                                                @continue
                                            @endif
                                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">#{{ $renewal->period }}</span>
                    </span>
                                                </td>
                                                <td class="h-px w-px whitespace-nowrap">
                                                    <a href="{{ route('admin.invoices.show', ['invoice' => $renewal->invoice]) }}" class="block px-6 py-2">
                                                        <span class="font-mono text-sm text-blue-600 dark:text-blue-500">{{ $renewal->invoice->identifier() }}</span>
                                                    </a>
                                                </td>
                                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatted_price($renewal->invoice->subtotal, $renewal->invoice->currency) }}</span>
                    </span>
                                                </td>
                                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $renewal->start_date->format('d/m/y') }} - {{ $renewal->end_date ? $renewal->end_date->format('d/m/y') : 'Undefined' }}</span>
                    </span>
                                                </td>
                                                <td class="h-px w-px whitespace-nowrap">
                                                    <a href="{{ route('admin.invoices.show', ['invoice' => $renewal->invoice]) }}" class="block">
                                        <span class="px-6 py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                   <i class="bi bi-eye-fill"></i>
                                            {{ __('global.view') }}
                                          </span>
                                        </span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if ($renewals->isEmpty())
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-gray-600 dark:text-gray-400">
                                                    {{ __('global.no_results') }}
                                                </td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            @if (staff_has_permission('admin.show_payment_methods'))
                                <div class="flex justify-between mt-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.services.subscription.index') }}
                                    </h2>
                                    <x-badge-state state="{{ $item->getSubscription()->state }}"></x-badge-state>
                                </div>
                                <form method="POST" action="{{ route('admin.services.subscription', ['service' => $item]) }}">
                                    @csrf
                                    @if ($paymentmethods->isNotEmpty())
                                        <div class="grid md:grid-cols-2 gap-2">
                                            <div>
                                                @include('admin/shared/select', ['name' => 'paymentmethod', 'options' => $paymentmethods, 'label' => __('client.payment-methods.paymentmethod'), 'value' => $item->getSubscription()->paymentmethod_id])

                                            </div>
                                            <div>
                                                @include('admin/shared/input', [ 'type' => 'number', 'name' => 'billing_day','label' => __('client.services.subscription.billing_day'), 'help' => __('client.services.subscription.billing_day_help'), 'attributes' => ['min' => 1, 'max' => 28], 'value' => $item->getSubscription()->billing_day ?? 5])
                                            </div>
                                        </div>
                                        <button
                                            class="btn btn-primary mt-2">{{ __('global.save') }}</button>
                                    @else
                                        <div class="alert text-yellow-800 bg-yellow-100 mt-2" role="alert">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>

                                            <p>{!! __('client.services.subscription.add_payments_method', ['url' => route('front.payment-methods.index')]) !!}</p>
                                        </div>
                                    @endif
                                    @if ($item->getSubscription()->isActive())
                                        <button class="btn btn-danger mt-2" name="cancel">{{ __('client.services.subscription.cancel') }}</button>
                                    @endif
                                </form>
                            @endif
                            @if (staff_has_permission('admin.create_invoice'))
                                @if (!$item->isOnetime())
                                    @if ($item->invoice_id == null)

                                        <form method="POST" action="{{ route('admin.services.renew', ['service' => $item]) }}">
                                            @csrf
                                            <h4 class="font-bold text-gray-600 dark:text-white mt-2 mb-2">
                                                {{ __($translatePrefix . '.renewals.create') }}
                                            </h4>
                                            @foreach (collect($item->pricingAvailable())->chunk(6) as $row)
                                                <ul class="flex flex-col sm:flex-row w-full">
                                                    @foreach($row as $pricing)
                                                        <li class="inline-flex items-center gap-x-2.5 py-3 px-4 text-sm font-medium bg-white border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg sm:-ms-px sm:mt-0 sm:first:rounded-se-none sm:first:rounded-es-lg sm:last:rounded-es-none sm:last:rounded-se-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                                                            <div class="relative flex items-start w-full">
                                                                <div class="flex items-center h-5">
                                                                    <input id="months-{{ $pricing->recurring }}" @if($loop->first) checked="checked" @endif name="billing" value="{{ $pricing->recurring }}" type="radio" class="border-gray-200 rounded-full disabled:opacity-50 dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                                                                </div>
                                                                <label for="months-{{ $pricing->recurring }}" class="ms-3 block w-full text-sm text-gray-600 dark:text-gray-500">
                                                                    {{ $pricing->recurring()['months'] == 0.5 ? 1 : $pricing->recurring()['months'] }} {{ $pricing->recurring()['months'] == 0.5 ? __('global.week') : __('global.month') }} - {{ $pricing->pricingMessage(false) }}
                                                                </label>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endforeach

                                            <h4 class="font-bold text-gray-600 dark:text-white mt-4 mb-2">
                                                {{ __($translatePrefix . '.renewals.fromexistinginvoice') }}
                                            </h4>
                                            <div class="flex flex-col">
                                                @include('/admin/shared/select', ['name' => 'invoice_id', 'label' => __('global.invoice'), 'options' => $invoices, 'value' => old('invoice_id')])
                                            </div>

                                            <div class="mt-2">
                                                <button class="btn btn-primary"> <i class="bi bi-eraser mr-2"></i>  {{ __($translatePrefix . '.renewals.btn2') }}</button>
                                            </div>
                                        </form>
                                    @else

                                        <div>
                                            <div class="flex rounded-lg shadow-sm mt-2">
                                                <input type="text" readonly class="input-text" id="invoice_url" value="{{ route('front.invoices.show', ['invoice' => $item->invoice]) }}">
                                                <button type="button" data-clipboard-target="#invoice_url" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                                    <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>

                                                    <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>

                                                </button>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.services.renew', ['service' => $item]) }}">
                                            @csrf
                                            <div class="mt-2">
                                                <button class="btn btn-primary"> <i class="bi bi-eraser mr-2"></i>  {{ __($translatePrefix . '.renewals.remove') }}</button>
                                            </div>
                                        </form>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div id="service-tab-configoptions" class="hidden" role="tabpanel" aria-labelledby="service-title-configoptions">
                    <div class="card card-body">
                        <div class="">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-3">
                                {{ __('provisioning.admin.services.configoptions.btn') }}
                            </h3>
                            <div>
                                @if (staff_has_permission('admin.manage_services'))
                                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead>
                                            <tr>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.config_option') }}
                    </span>
                                                    </div>
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.value') }}
                    </span>
                                                    </div>
                                                </th>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                      {{ __('client.services.expire_date') }}
                                    </span>
                                                    </div>
                                                </th>

                                                <th scope="col" class="px-6 py-3 text-start">
                                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('store.price') }}
                    </span>
                                                    </div>
                                                </th>


                                                <th scope="col" class="px-6 py-3 text-end"></th>
                                            </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($item->configoptions as $configoption)
                                                @if (!$configoption->option)
                                                    @continue
                                                @endif
                                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $configoption->option->name }}</span>
                    </span>
                                                    </td>

                                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $configoption->formattedValue() }}</span>
                    </span>
                                                    </td>
                                                    <td class="h-px w-px whitespace-nowrap">
                                                        <x-service-days-remaining expires_at="{{ $configoption->expires_at }}"></x-service-days-remaining>
                                                    </td>
                                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $configoption->formattedPrice($item->currency) }}</span>
                    </span>
                                                    </td>

                                                    <td class="h-px w-px whitespace-nowrap">

                                                        <a href="{{ route('admin.configoptions_services.show', ['configoptions_service' => $configoption]) }}">
                                        <span class="px-1 py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                                        </a>
                                                        <form method="POST" action="{{ route('admin.configoptions_services.show', ['configoptions_service' => $configoption]) }}" class="inline confirmation-popup">
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
                                            @if ($item->configoptions->isEmpty())
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-gray-600 dark:text-gray-400">
                                                        {{ __('global.no_results') }}
                                                    </td>
                                                </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @if ($item->getConfigOptionsAvailable()->isNotEmpty())
                                    <h4 class="font-bold text-gray-800 dark:text-white mt-3">
                                        {{ __('provisioning.admin.services.configoptions.addoption') }}
                                    </h4>

                                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                                        {{ __('provisioning.admin.services.configoptions.addoption_help') }}
                                    </p>
                                    <form method="POST" action="{{ route('admin.configoptions_services.store') }}">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="flex flex-col">
                                                @include('/admin/shared/select', ['name' => 'config_option_id', 'label' => __('provisioning.config_option'), 'options' => $item->getConfigOptionsAvailable()->pluck('name', 'id'), 'value' => old('configoption_id')])

                                            </div>
                                            <div class="flex flex-col">
                                                @include('/admin/shared/input', ['name' => 'value', 'label' => __('global.value'), 'value' => old('value')])
                                            </div>
                                            <input type="hidden" name="service_id" value="{{ $item->id }}">
                                        </div>

                                        <button class="btn btn-primary mt-2"> <i class="bi bi-plus mr-2"></i>  {{ __('global.add') }}</button>
                                    </form>
                                @endif
                                    @endif
                    </div>
                </div>
                    </div>
                </div>

                <div id="service-tab-upgrade" class="hidden" role="tabpanel" aria-labelledby="service-title-upgrade">
                    <div class="card card-body">
                        <h3 class="font-bold text-gray-800 dark:text-white mb-3">
                            {{ __('provisioning.admin.services.upgrade.btn') }}
                        </h3>

                        @if (staff_has_permission('admin.manage_services'))
                            <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                    <tr>

                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.old_product') }}
                    </span>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.new_product') }}
                    </span>
                                            </div>
                                        </th>


                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center gap-x-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                      {{ __('global.invoice') }}
                                    </span>
                                            </div>
                                        </th>

                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.upgraded') }}
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
                                    @foreach($item->upgrades as $upgrade)
                                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                            <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                    <a href="{{ route('admin.products.show', ['product' => $upgrade->oldProduct]) }}">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $upgrade->oldProduct->trans('name') }}</span>
                                                    </a>
                                                </span>
                                            </td>

                                            <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                    <a href="{{ route('admin.products.show', ['product' => $upgrade->newProduct]) }}">
                                                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ $upgrade->newProduct->trans('name') }}</span>
                                                    </a>
                                                </span>
                                            </td>

                                            <td class="h-px w-px whitespace-nowrap">
                                                @if ($upgrade->invoice == null)
                                                    <span class="block px-6 py-2">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('global.none') }}</span>
                                                    </span>
                                                @else
                                                <span class="block px-6 py-2">
                                            <a href="{{ route('admin.invoices.show', ['invoice' => $upgrade->invoice]) }}">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $upgrade->invoice->identifier() }}</span>
                                            </a>
                                                </span>
                                                @endif
                                            </td>

                                            <td class="h-px w-px px-6 whitespace-nowrap">

                                                @if ($upgrade->upgraded)
                                                    <span class="mx-auto py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
  <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
  {{ __('global.yes') }}
</span>
                                                @else
                                                    <span class="mx-auto py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
  <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
  {{ __('global.no') }}
</span>
                                                @endif</td>
                                            <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $upgrade->created_at->format('d/m/y H:i') }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($item->upgrades->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-gray-600 dark:text-gray-400">
                                                {{ __('global.no_results') }}
                                            </td>
                                        </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        @if ($item->product_id != null)
                            <form method="POST" action="{{ route($routePath . '.upgrade', ['service' => $item]) }}">
                                @csrf
                                <h3 class="font-bold text-gray-800 dark:text-white mt-2">
                                    {{ __('provisioning.admin.services.upgrade.makeupgrade') }}
                                </h3>
                                <div class="flex flex-col">
                                    @include('/admin/shared/select', ['name' => 'product_id', 'label' => __('provisioning.admin.upgrade_services.new_product'), 'options' => $upgrade_products, 'value' => old('product_id')])
                                </div>
                                <div>
                                    @include('/admin/shared/select', ['name' => 'type', 'label' => __('provisioning.admin.services.upgrade.upgrade_type'), 'options' => __('provisioning.admin.services.upgrade.upgrade_types'), 'value' => old('type')])
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-primary"> <i class="bi bi-arrow-up-right mr-2"></i>  {{ __('provisioning.admin.services.upgrade.makeupgrade') }}</button>
                                </div>
                            </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="md:w-1/4">
                <div>
                    @if (staff_has_permission('admin.manage_services'))
                        @if (!$item->isExpired())
                            <form method="POST" action="{{ route('admin.services.action', ['service' => $item, 'action' => 'expire']) }}" class="confirmation-popup">
                                @csrf
                                <button type="submit" class="btn btn-danger w-full mb-2 text-left">
                                    <i class="bi bi-trash mr-2"></i>
                                    {{ __('provisioning.admin.services.terminate.btn') }}
                                </button>
                            </form>
                        @endif
                        @if ($item->isActivated())
                            <button type="button" class="btn btn-warning w-full mb-2 text-left" data-hs-overlay="#suspend-overlay">
                                <i class="bi bi-ban mr-2"></i>
                                {{ __('provisioning.admin.services.suspend.btn') }}
                            </button>
                        @endif
                        @if($item->isSuspended())
                            <button class="btn btn-success mb-2 w-full text-left" data-hs-overlay="#suspend-overlay">
                                <i class="bi bi-check mr-2"></i>
                                {{ __('provisioning.admin.services.unsuspend.btn') }}
                            </button>
                        @endif
                    @endif

                        @if (staff_has_permission('admin.deliver_services'))
                            @if ($item->isPending())
                                <form method="POST" action="{{ route('admin.services.delivery', ['service' => $item]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-full mb-2 text-left">
                                        <i class="bi bi-truck mr-2"></i>
                                        {{ __('provisioning.admin.services.delivery.btn') }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.services.reinstall', ['service' => $item]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-full mb-2 text-left" onclick="return confirmation();">
                                        <i class="bi bi-truck mr-2"></i>
                                        {{ __('provisioning.admin.services.delivery.btn2') }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    @if (staff_has_permission('admin.show_customers') && $item->customer)

                        <a class="btn bg-blue-600 w-full text-left mb-2" href="{{ route('admin.customers.show', ['customer' => $item->customer]) }}">
                            <i class="bi bi-people mr-2"></i>
                            {{ __('provisioning.admin.services.show.customerbtn') }}
                            <i class="bi bi-box-arrow-up-right mr-auto"></i>
                        </a>
                    @endif
                    @if (staff_has_permission('admin.manage_services'))
                        <button class="btn bg-red-500 mb-2 w-full text-left" data-hs-overlay="#cancel-overlay">
                            <i class="bi bi-trash2 mr-2"></i>
                            @if ($item->isPending())
                                {{ __('provisioning.admin.services.cancel.delivery') }}
                            @else
                            {{ __('provisioning.admin.services.cancel.btn') }}
                            @endif
                        </button>
                    @endif

                        @if (staff_has_permission('admin.show_metadata'))

                            <button class="btn btn-secondary mb-2 w-full text-left" id="metadata-button" data-hs-overlay="#metadata-overlay">
                                <i class="bi bi-database mr-2"></i>
                                {{ __('admin.metadata.title') }}
                            </button>
                        @endif
                </div>

                <div class="card card-sm">
                    @include('admin/shared/input', ['name' => 'uuid', 'label' => "UUID", 'value' => $item->uuid, 'readonly' => true])
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            @include('admin/shared/input', ['name' => 'id', 'label' => "ID", 'value' => $item->id, 'readonly' => true])
                        </div>
                        <div>
                            @include('admin/shared/input', ['name' => 'created_at', 'label' => __('global.created'), 'value' => $item->created_at->format('d/m/y H:i'), 'readonly' => true])
                        </div>
                    </div>
                </div>
                @if (count($tabs) != 0)
                    <div class="flex flex-col bg-white shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">

                        <div class="w-full flex flex-col">
                            @foreach ($tabs as $tab)
                                <a {{ !$tab->active ? 'disabled="true"' : '' }} class="{{ $loop->first ? 'provisioning-tab-first ' : ($loop->last ? 'provisioning-tab-last ' : '') }}{{ !$tab->active ? 'provisioning-tab-disabled' : (($current_tab && $current_tab->uuid == $tab->uuid) ? 'provisioning-tab-active' : 'provisioning-tab') }}" href="{{ $tab->active ? $tab->route($item->id, true) : '#' }}" {!! $tab->popup ? 'is="popup-window"' : '' !!}  {!! $tab->newwindow ? 'target="_blank"' : '' !!}>
                                    {!! $tab->icon !!}
                                    {{ $tab->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @if (staff_has_permission('admin.manage_services'))

        <div id="cancel-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __($translatePrefix . '.cancel.btn') }}
                </h3>
                <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#cancel-overlay">
                    <span class="sr-only">{{ __('global.closemodal') }}</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4">

                <form method="POST" action="{{ route('admin.services.action', ['service' => $item, 'action' => $item->isPending() ? 'cancel_delivery' : 'cancel']) }}">
                    @csrf

                    @if ($item->isPending())
                    <p class="text-gray-800 dark:text-gray-400">
                        {{ __($translatePrefix . '.cancel.pending') }}
                    </p>
                    <button class="btn btn-primary w-full mt-10">
                        <i class="bi bi-check mr-2"></i>{{ __($translatePrefix . '.cancel.delivery') }}
                    </button>
                    @else
                        @if ($item->cancelled_reason != NULL)
                            @include('/admin/shared/select', ['name' => 'reason', 'label' => __('client.services.cancel.reason'), 'options' => \App\Models\Provisioning\CancellationReason::getReasons(), 'value' => old('reason')])
                            @include('/admin/shared/textarea', ['name' => 'message', 'label' => __($translatePrefix. '.cancel.message'), 'value' => $item->cancelled_reason])
                            @if (!$item->isOnetime())
                                @include('/admin/shared/input', ['name' => 'expiration', 'label' => __('client.services.cancel.expiration'), 'value' => $item->cancelled_at->format('d/m/y H:i')])
                            @endif
                            <button class="btn btn-primary w-full mt-10"> <i class="bi bi-check mr-2"></i>{{ __($translatePrefix . '.cancel.restore') }}</button>

                        @else
                            @include('/admin/shared/select', ['name' => 'reason', 'label' => __('client.services.cancel.reason'), 'options' => \App\Models\Provisioning\CancellationReason::getReasons(), 'value' => old('reason')])
                            @include('/admin/shared/textarea', ['name' => 'message', 'label' => __('client.services.cancel.message'), 'value' => old('message')])
                            @if (!$item->isOnetime())
                                @include('/admin/shared/select', ['name' => 'expiration', 'label' => __('client.services.cancel.expiration'), 'options' => \App\Models\Provisioning\CancellationReason::getCancellationMode($item), 'value' => old('expiration')])
                            @endif
                            <button class="btn btn-primary w-full mt-10"> <i class="bi bi-trash2 mr-2"></i>{{ __($translatePrefix . '.cancel.title') }}</button>

                        @endif
                        @endif
                </form>
            </div>
        </div>
        <div id="suspend-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    @if ($item->isActivated())
                        {{ __($translatePrefix . '.suspend.title') }}
                    @else
                        {{ __($translatePrefix . '.unsuspend.title') }}
                    @endif
                </h3>
                <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#suspend-overlay">
                    <span class="sr-only">{{ __('global.closemodal') }}</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4">

                <form method="POST" action="{{ route('admin.services.action', ['service' => $item, 'action' => $item->isActivated() ? 'suspend' : 'unsuspend']) }}">
                    <div class="text-gray-800 dark:text-gray-400">

                    @csrf
                    @if ($item->isActivated())
                        @include('/admin/shared/textarea', ['name' => 'reason', 'label' => __('provisioning.admin.services.suspend.reason'), 'value' => old('reason', $item->suspend_reason)])
                        <div class="mt-2">
                            @include('/admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                        </div>
                    @elseif ($item->suspended_at != null)
                        @include('/admin/shared/textarea', ['name' => 'reason', 'label' => __('provisioning.admin.services.suspend.reason'), 'value' => $item->suspend_reason, 'disabled' => true])
                        @include('/admin/shared/input', ['name' => 'suspend_at', 'label' => __('provisioning.admin.services.suspend.suspend_at'), 'disabled' => true,'value' => $item->suspended_at->format('d/m/y H:i')])
                    @endif
                    @if ($item->isActivated())
                        <button class="btn btn-warning w-full mt-10"> <i class="bi bi-ban mr-2"></i>  {{ __($translatePrefix . '.suspend.btn') }}</button>
                    @else
                        <button class="btn btn-success w-full mt-10"> <i class="bi bi-check mr-2"></i>  {{ __($translatePrefix . '.unsuspend.btn') }}</button>@endif
                        </div>
                </form>
            </div>
        </div>
        @endif
        @if (staff_has_permission('admin.manage_metadata'))
        <div id="metadata-overlay" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __($translatePrefix . '.data.title') }}
                </h3>
                <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#metadata-overlay">
                    <span class="sr-only">{{ __('global.closemodal') }}</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.services.update_data', ['service' => $item]) }}#metadata">
                    @csrf
                    @include('/admin/shared/textarea', ['name' => 'data', 'label' => __('provisioning.admin.services.data.orderdata'), 'value' => old('data', json_encode($item->data, JSON_PRETTY_PRINT)), 'rows' => 10])
                    <button class="btn btn-primary w-full mt-2"> <i class="bi bi-check mr-2"></i>  {{ __('global.save') }}</button>
                </form>
            </div>
            @include('admin/metadata/table', ['item' => $item])
        </div>
    </div>
    @endif
    @include('admin/shared/pricing/collapse')

@endsection
