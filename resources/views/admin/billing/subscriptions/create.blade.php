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
@extends('admin/layouts/admin')
@section('title',  __($translatePrefix . '.create.title'))
@section('scripts')
    <script>
        const changeFormAction = () => {
            const form = document.querySelector('#subscription-form');
            const serviceId = document.querySelector('select[name="service_id"]').value;
            const url = `{{ route('admin.services.subscription', ['service' => ':service']) }}`;
            form.setAttribute('action', url.replace(':service', serviceId));
        }
        document.addEventListener('DOMContentLoaded', function() {
            changeFormAction();
            document.querySelector('select[name="service_id"]').addEventListener('change', changeFormAction);
        });
    </script>
@section('content')

    <div class="container mx-auto">
        @include('admin/shared/alerts')

        <form method="{{ $step == 1 ? 'GET' : 'POST' }}" class="card" id="subscription-form" action="{{ $step == 1 ? '' : route($routePath . '.store') }}">
                <div class="card-heading">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __($translatePrefix . '.create.title') }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __($translatePrefix. '.create.subheading') }}
                        </p>
                    </div>
                    @if (!empty($customers))
                    <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                        <button class="btn btn-primary">
                            {{ __('admin.create') }}
                        </button>
                    </div>
                        @endif
                </div>
                @if ($step == 1)
                    @if (empty($customers))

                    <div class="col-span-12">

                        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                            <i class="bi bi-people text-6xl text-gray-800 dark:text-gray-200"></i>
                            <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                {{ __('admin.customers.create.create_customer_help') }}
                            </p>
                            <a href="{{ route('admin.customers.create') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('admin.customers.create.title') }}</a>
                        </div>
                    </div>
                    @else
                        @include('admin/shared/search-select', ['name' => 'customer_id', 'label' => __('global.customer'), 'options' => $customers, 'value' => 1])
                    @endif
                    @else
                    @csrf
                @if ($paymentMethods->isNotEmpty() && !empty($services))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            @include('admin/shared/select', ['name' => 'service_id', 'label' => __('global.service'), 'options' => $services, 'value' => old('service_id', current($services))])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/select', ['name' => 'paymentmethod', 'options' => $paymentMethods, 'label' => __('client.payment-methods.paymentmethod'), 'value' => null])
                        </div>
                    </div>
                    @else
                        @if ($paymentMethods->isEmpty())
                        <div class="alert text-yellow-800 bg-yellow-100 mt-2" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>

                            <p>{!! __('client.services.subscription.add_payments_method', ['url' => route('front.payment-methods.index')]) !!}</p>
                        </div>
                        @endif
                    @if (empty($services))

                                <div class="alert text-yellow-800 bg-yellow-100 mt-2" role="alert">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>

                                    <p>{!! __($translatePrefix . '.services_empty') !!}</p>
                                </div>
                        @endif
                    @endif
                @endif
            </form>
        </div>

@endsection
