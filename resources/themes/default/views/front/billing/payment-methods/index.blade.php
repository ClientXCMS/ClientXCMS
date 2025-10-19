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
@section('scripts')
    <script>
        function showBillingDayForm(serviceId, currentDay) {
            document.getElementById('billing-day-display-' + serviceId).style.display = 'none';
            var container = document.getElementById('billing-day-form-' + serviceId);
            container.classList.remove('hidden');
            container.querySelector('input[name="billing_day"]').focus();
        }
        function hideBillingDayForm(serviceId) {
            document.getElementById('billing-day-display-' + serviceId).style.display = '';
            document.getElementById('billing-day-form-' + serviceId).classList.add('hidden');
        }
    </script>
@endsection
@section('title', __('client.payment-methods.index'))
@section('content')
    <div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @include('shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    @include('front/billing/payment-methods/card', ['sources' => $sources])

                    <div class="card">

                        <div class="flex justify-between card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('client.payment-methods.invoices') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('client.payment-methods.invoices_description') }}
                                </p>
                            </div>

                            <div>

                                <a class="py-1 px-4 inline-flex gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('front.invoices.index') }}">
                                    {{ __('global.seemore') }}
                                    <svg class="flex-shrink-0 w-4 h-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </div>
                        </div>
                        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                        {{ __('global.invoice') }}
                                    </th>
                                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                        {{ __('store.total') }}
                                    </th>
                                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                        {{ __('client.invoices.paymethod') }}
                                    </th>
                                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                        {{ __('global.date') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($paidInvoicesWithPaymentMethod as $invoice)
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td class="px-6 py-2">
                                            <a href="{{ route('front.invoices.show', ['invoice' => $invoice]) }}" class="font-mono text-sm text-blue-600 dark:text-blue-500">
                                                {{ $invoice->identifier() }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ formatted_price($invoice->subtotal, $invoice->currency) }}
                                </span>
                                        </td>
                                        <td class="px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invoice->gateway->name ?? __('global.unknown') }}
                                </span>
                                        </td>
                                        <td class="px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invoice->created_at->format('d/m/Y H:i') }}
                                </span>
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($paidInvoicesWithPaymentMethod->isEmpty())
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                <p class="text-sm text-gray-800 dark:text-gray-400">
                                                    {{ __('global.no_results') }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">

                            <div class="flex justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.services.subscription.manage_subscriptions.index') }}
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('client.services.subscription.manage_subscriptions.index_description') }}
                                    </p>
                                </div>
                                <div>
                                    <a class="py-1 px-4 inline-flex gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('front.invoices.index') }}">
                                        {{ __('global.seemore') }}
                                        <svg class="flex-shrink-0 w-4 h-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.name') }}
                                        </th>
                                        <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.status') }}
                                        </th>

                                        <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('client.services.subscription.manage_subscriptions.next_renewal') }}
                                        </th>

                                        <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('client.payment-methods.paymentmethod') }}
                                        </th>
                                        <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.actions') }}
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($subscribableServices as $service)
                                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                            <td class="px-6 py-2">
                                                <a href="{{ route('front.services.show', $service) }}">

                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $service->name }}
                                </span>
                                                </a>
                                            </td>
                                            <td class="px-6 py-2">
                                                <x-badge-state state="{{ $service->getSubscription()->state }}"></x-badge-state>

                                            </td>
                                            <form method="POST" action="{{ route('front.services.subscription', ['service' => $service]) }}">
                                                <td class="px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400 billing-day-display" id="billing-day-display-{{ $service->id }}">
                                                    {{ $service->getSubscription()->isActive() ? $service->getSubscription()->getNextPaymentDate() : '--' }}
                                                    @if ($service->getSubscription()->isActive())
                                                        <button type="button" class="ml-2 py-1 px-2 inline-flex mr-2 justify-center items-center gap-2 rounded-lg border font-medium bg-slate text-slate-700 shadow-sm align-middle hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800" onclick="showBillingDayForm({{ $service->id }}, {{ $service->getSubscription()->billing_days ?? 5 }})">
                                                            <i class="bi bi-pencil-square"></i>
                                                            {{ __('global.edit')}}
                                                        </button>
                                                    @endif
                                                </span>
                                                    @csrf
                                                    <div class="flex items-center hidden billing-day-form" id="billing-day-form-{{ $service->id }}">
                                                        @include('shared/input', [
                                                            'type' => 'number',
                                                            'name' => 'billing_day',
                                                            'attributes' => [
                                                                'min' => 1,
                                                                'max' => 28
                                                            ],
                                                            'class' => 'form-input w-16',
                                                            'id' => "billing-day-input-{$service->id}",
                                                            'value' => $service->getSubscription()->billing_day ?? 5,
                                                            'required' => true
                                                        ])
                                                    </div>
                                                </td>

                                                @csrf
                                                @if (auth('web')->user()->getPaymentMethodsArray()->isNotEmpty())
                                                    <td class="px-6 py-2">
                                                        @include('shared/select', [
                                                            'name' => 'paymentmethod',
                                                            'options' => auth('web')->user()->getPaymentMethodsArray(),
                                                            'value' => $service->getSubscription()->paymentmethod_id
                                                        ])
                                                    </td>
                                                    <td class="px-6 py-2">
                                                        <div class="flex justify-content-between">
                                                            <button type="submit" class="py-1 px-2 inline-flex mr-2 justify-center items-center gap-2 rounded-lg border font-medium bg-slate text-slate-700 shadow-sm align-middle hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                                <i class="bi bi-check-circle"></i>
                                                                {{ __('global.save') }}
                                                            </button>
                                                            @if ($service->getSubscription()->isActive())
                                                                <button type="submit" name="cancel" class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                                    <i class="bi bi-x-circle"></i>
                                                                    {{ __('global.cancel') }}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="px-6 py-2" colspan="2">
                                                        <div class="alert text-yellow-800 bg-yellow-100 mt-2" role="alert">
                                                            <p>{!! __('client.services.subscription.add_payments_method', ['url' => route('front.payment-methods.index')]) !!}</p>
                                                        </div>
                                                    </td>
                                                @endif

                                            </form>
                                        </tr>
                                    @endforeach

                                    @if ($subscribableServices->isEmpty())
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
                        <div class="card">
                            <div class="card-heading">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.payment-methods.add') }}
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('client.payment-methods.add_description') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach ($gateways as $gateway)
                                    <form method="POST" action="{{ route('front.payment-methods.add', $gateway->id) }}" id="payment-form-{{ $gateway->uuid }}">
                                        @csrf
                                        {!! $gateway->paymentType()->sourceForm() !!}
                                    </form>
                                @endforeach
                            </div>
                        </div>

                        @if (app('extension')->extensionIsEnabled('fund'))
                            @include('fund::card')
                        @endif
                        @if (app('extension')->extensionIsEnabled('giftcard'))
                            @include('giftcard::card')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
