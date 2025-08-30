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

@extends('layouts/client')
@section('title', __('client.invoices.details'))
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethodSelect = document.querySelector('select[name="paymentmethod"]');
            const paymentMethodBtn1 = document.querySelector('.paymentmethod-btn1');
            const paymentMethodBtn2 = document.querySelector('.paymentmethod-btn2');
            const onPaymentMethodChange = () => {
                if (paymentMethodSelect.value === 'none') {
                    paymentMethodBtn1.classList.add('hidden');
                    paymentMethodBtn2.classList.remove('hidden');
                } else {
                    paymentMethodBtn1.classList.remove('hidden');
                    paymentMethodBtn2.classList.add('hidden');
                }
            };
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', onPaymentMethodChange);
                onPaymentMethodChange();
            }
        });
    </script>
@section('content')
    <div class="max-w-[85rem] py-5 lg:py-7 mx-auto">
    <div class="sm:w-11/12 lg:w-3/4 mx-auto">
        @include('shared/alerts')

        <div class="card">
                <div class="flex justify-between">
                    <div>
                        <img class="mx-auto h-12 w-auto mt-4" src="{{ setting('app_logo_text') }}" alt="{{ setting('app_name') }}">

                    </div>

                    <div class="text-end">
                        <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-200">{{ __('global.invoice') }} #</h2>
                        <span class="mt-1 block text-gray-500">{{ $invoice->identifier() }}</span>

                        <address class="mt-4 not-italic text-gray-800 dark:text-gray-200">
                            {!! nl2br(setting('app_address')) !!}
                        </address>
                    </div>
                </div>

                <div class="mt-8 grid sm:grid-cols-2 gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.billto', ['name' => $address[0]]) }}</h3>
                        <address class="mt-2 not-italic text-gray-500">

                            @foreach ($address as $i => $line)
                                @if ($i == 0)
                                    @continue
                                @endif
                                {{ $line }}<br/>
                            @endforeach
                        </address>
                    </div>

                    <div class="space-y-2">
                        <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.invoice_date') }}:</dt>
                                <dd class="col-span-2 text-gray-500">{{ $invoice->created_at->format('d/m/y H:i') }}</dd>
                            </dl>

                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.due_date') }}:</dt>
                                <dd class="col-span-2 text-gray-500">{{ $invoice->due_date->format('d/m/y H:i') }}</dd>
                            </dl>

                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('global.status') }}:</dt>
                                <dd class="col-span-2 text-gray-500"><x-badge-state state="{{ $invoice->status }}"></x-badge-state></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="border border-gray-200 p-4 rounded-lg space-y-4 dark:border-gray-700">
                        <div class="hidden sm:grid sm:grid-cols-6">
                            <div class="sm:col-span-2 text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.itemname')  }}</div>
                            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.qty') }}</div>
                            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('store.unit_price') }}</div>
                            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('store.setup_price') }}</div>
                            <div class="text-end text-xs font-medium text-gray-500 uppercase">{{ __('store.price') }}</div>
                        </div>
                        @foreach ($invoice->items as $item)
                        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-6 gap-2">
                            <div class="sm:col-span-2">
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.itemname') }}</h5>
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $item->name }}</p>
                                @if ($item->canDisplayDescription())
                                <p class="font-medium text-gray-400">{!! nl2br($item->description) !!}</p>
                                @endif
                                @if ($item->getDiscount(false) != null)
                                    <p class="font-medium text-gray-400 text-start">{{ $item->getDiscountLabel() }}</p>
                                @endif
                            </div>
                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.qty') }}</h5>
                                <p class="text-gray-800 dark:text-gray-200">{{ $item->quantity }}</p>
                            </div>
                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.unit_price') }}</h5>
                                <div class="block">
                                    <p class="text-gray-800 dark:text-gray-200 text-start">{{ formatted_price($item->unit_price_ht, $invoice->currency) }}</p>
                                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_price != 0)
                                    <p class="font-medium text-gray-400 text-start">-{{ formatted_price($item->getDiscount()->sub_price, $invoice->currency) }}</p>
                                        @endif
                                </div>
                            </div>
                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.setup_price') }}</h5>
                                <div class="block">
                                    <p class="text-gray-800 dark:text-gray-200 text-start">{{ formatted_price($item->unit_setup_ht, $invoice->currency) }}</p>
                                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0)
                                        <p class="font-medium text-gray-400 text-start">-{{ formatted_price($item->getDiscount()->sub_setup, $invoice->currency) }}</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.price') }}</h5>
                                <div class="block">
                                    <p class="text-gray-800 dark:text-gray-200 md:text-end sm:text-start">{{ formatted_price($item->price(), $invoice->currency) }}</p>
                                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0 || $item->getDiscount()->sub_price != 0)
                                        <p class="font-medium text-gray-400 md:text-end sm:text-start">-{{ formatted_price($item->getDiscount()->sub_price + $item->getDiscount()->sub_setup, $invoice->currency) }}</p>
                                    @endif
                                </div>
                            </div>

                        </div>
                            @endforeach

                        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>
                        <div class="grid grid-cols-1 sm:grid-cols-6 gap-2">
                            <div class="sm:col-span-5 hidden sm:grid">
                                <p class="sm:text-end font-semibold text-gray-800 dark:text-gray-200 text-end">{{ __('store.subtotal') }}</p>
                            </div>

                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.subtotal') }}</h5>

                                <p class="text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ formatted_price($invoice->subtotal, $invoice->currency) }}</p>
                            </div>

                        </div>
                        @if ($invoice->balance > 0)
                            <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

                            <div class="grid grid-cols-1 sm:grid-cols-6 gap-2">
                                <div class="sm:col-span-5 hidden sm:grid">
                                    <p class="sm:text-end font-semibold text-gray-800 dark:text-gray-200 text-end">{{ __('client.invoices.balance.title') }}</p>
                                </div>

                                <div>
                                    <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.balance.title') }}</h5>

                                    <p class="text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ formatted_price($invoice->balance, $invoice->currency) }}</p>
                                </div>

                            </div>
                        @endif
                        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <div class="sm:col-span-5 hidden sm:grid">
                                <p class="sm:text-end font-semibold text-gray-800 dark:text-gray-200 text-end">{{ __('store.vat') }}</p>
                            </div>

                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.vat') }}</h5>

                                <p class="text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ formatted_price($invoice->tax, $invoice->currency) }}</p>
                            </div>

                        </div>

                        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <div class="col-span-5 hidden sm:grid">
                                <p class="font-semibold text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ __('store.total') }}</p>
                            </div>

                            <div>
                                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.total') }}</h5>

                                <p class="text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ formatted_price($invoice->total, $invoice->currency) }}</p>
                            </div>

                        </div>
                    </div>
                </div>
            @if ($invoice->external_id != null)

                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="overflow-hidden">
                                <div class="border border-gray-200 p-2 rounded-lg space-y-2 dark:border-gray-700 mt-3">

                                    <div class="overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                            <thead>
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                                    {{ __('client.invoices.paymethod') }}</th>
                                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                                    {{ __('client.invoices.paid_date') }}</th>
                                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                                    {{ __('admin.invoices.show.external_id') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $invoice->gateway != null ? $invoice->gateway->name : $invoice->paymethod }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/y H:i') : 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                    {{ $invoice->external_id }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
                <!-- End Table -->

                <!-- Flex -->
                <div class="mt-8 flex sm:justify-end">
                    <div class="w-full max-w-xl sm:text-end space-y-2 text-end">
                            @if ($invoice->canPay())
                            <dl class="grid gap-x-3">
                                <dt class="col-span-5">
                                    @if ($invoice->total == 0)
                                        <a class="paymentmethod-btn2 hs-dropdown-toggle py-2 px-3 inline-flex items-center rounded-lg gap-x-2 text-sm font-semibold border border-transparent bg-indigo-100 text-indigo-800 hover:bg-indigo-200 disabled:opacity-50 disabled:pointer-events-none" href="{{ route('front.invoices.pay', ['invoice' => $invoice, 'gateway' => 'none']) }}">
                                            <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9.5h20"/></svg>
                                            {{ __('client.invoices.pay') }}
                                        </a>
                                    @else

                                        @if (auth('web')->user()->balance > 0 && setting('allow_add_balance_to_invoices'))
                                            <form method="POST" action="{{ route('front.invoices.balance', ['invoice' => $invoice]) }}">
                                                <p class="font-semibold mt-2 dark:text-white">{{ __('client.invoices.pay_with_balance') }}</p>
                                                @csrf
                                                <div class="grid sm:grid-cols-5 gap-x-3">
                                                    <div class="col-span-4">
                                                        @include('shared/input', ['name' => 'amount', 'value' => auth('web')->user()->balance > $invoice->total ? $invoice->total : auth('web')->user()->balance])
                                                    </div>
                                                <button type="submit" class="mt-3 hs-dropdown-toggle py-2 px-3 inline-flex items-center rounded-lg gap-x-2 text-sm font-semibold border border-transparent bg-indigo-100 text-indigo-800 hover:bg-indigo-200 disabled:opacity-50 disabled:pointer-events-none">
                                                    <i class="bi bi-plus"></i>
                                                    {{ __('global.add') }}
                                                </button>
                                            </div>
                                            </form>
                                        @endif
                                        @if (auth('web')->user()->paymentMethods()->isNotEmpty())
                                            <form method="POST" action="{{ route('front.payment-methods.pay', ['invoice' => $invoice]) }}">
                                                @csrf
                                                <h3 class="font-semibold mt-2 dark:text-white">{{ __('store.checkout.choose_payment_method') }}</h3>
                                                @include('shared/select', [
                                                            'name' => 'paymentmethod',
                                                            'options' => auth('web')->user()->getPaymentMethodsArray()->merge(['none' => __('store.checkout.not_use_payment_method')]),
                                                            'value' => 'none'
                                                        ])
                                                <button type="submit" class="paymentmethod-btn1 mt-3 hs-dropdown-toggle py-2 px-3 inline-flex items-center rounded-lg gap-x-2 text-sm font-semibold border border-transparent bg-indigo-100 text-indigo-800 hover:bg-indigo-200 disabled:opacity-50 disabled:pointer-events-none">
                                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9.5h20"/></svg>
                                                    {{ __('client.invoices.pay') }}
                                                </button>
                                            </form>
                                        @endif
                                    <div class="hs-dropdown relative inline-flex">

                                        <button class="paymentmethod-btn2 hs-dropdown-toggle py-2 px-3 inline-flex items-center rounded-lg gap-x-2 text-sm font-semibold border border-transparent bg-indigo-100 text-indigo-800 hover:bg-indigo-200 disabled:opacity-50 disabled:pointer-events-none">
                                            <svg class="flex-shrink-0 w-4 h-4"  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9.5h20"/></svg>
                                            {{ __('client.invoices.pay') }}
                                            <svg class="hs-dropdown-open:rotate-180 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>

                                        </button>

                                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[15rem] bg-white shadow-md rounded-lg p-2 mt-2 dark:bg-gray-800 dark:border dark:border-gray-700 dark:divide-gray-700 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full" aria-labelledby="hs-dropdown-default">
                                            @foreach ($gateways as $gateway)
                                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:bg-gray-700" href="{{ route('front.invoices.pay', ['invoice' => $invoice, 'gateway' => $gateway->uuid]) }}">
                                                {{ $gateway->getGatewayName() }}
                                            </a>
                                            @endforeach
                                        </div>
                                    </div>

                                        @endif
                                </dt>
                            </dl>
                                @endif

                    </div>
                </div>

                <div class="mt-8 sm:mt-12">

                    @if (!empty(setting("invoice_terms")))
                        <h6 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.terms') }}</h6>
                        <p class="text-gray-500 mb-3">{!! nl2br(setting("invoice_terms", "You can change this details in Invoice configuration.")) !!}</p>
                    @endif
                    @if ($invoice->paymethod == 'bank_transfert' && $invoice->status != 'paid')
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.banktransfer.title') }}</h4>
                        <p class="text-gray-500">{!! nl2br(setting("bank_transfert_details", "You can change this details in Bank transfer configuration.")) !!}</p>
                        @elseif ($invoice->status == 'paid')
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.thank') }}</h4>
                    <p class="text-gray-500">{{ __('client.invoices.thankmessage') }}</p>
                        @endif

                </div>

                <p class="mt-5 text-sm text-gray-500">© {{ date('Y') }} {{ config('app.name') }}.</p>
            </div>

            <div class="mt-6 flex justify-end gap-x-3 print:hidden">
                <a class="py-2 px-3 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-gray-800 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800" href="{{ route('front.invoices.download', ['invoice' => $invoice]) }}">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    {{ __('client.invoices.download') }}
                </a>
                <a target="_blank" href="{{ route('front.invoices.pdf', ['invoice' => $invoice]) }}" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="#">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    {{ __('client.invoices.print') }}
                </a>
            </div>
        </div>
    </div>
@endsection
