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
@extends('layouts/client')
@section('title', __('client.services.options.index'))
@section('content')
    <div class="max-w-[85rem] py-5 lg:py-7 mx-auto">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="md:w-3/4">
                @include('shared/alerts')

                <div class="card">
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('client.services.options.index') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('client.services.options.index_description') }}
                            </p>
                        </div>
                    </div>

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
                                      {{ __('global.expiration') }}
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
                                @foreach($service->configoptions as $configoption)
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
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $configoption->formattedPrice($service->currency) }}</span>
                    </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($service->configoptions->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-gray-600 dark:text-gray-400">
                                            {{ __('global.no_results') }}
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <div class="md:w-1/4">
                <div class="grid grid-col-1">

                    @if ($service->canRenew())
                        @if ($service->isFree())
                            <a class="btn-action-with-icon mb-2 p-3" href="{{ route('front.services.renew', ['service' => $service, 'gateway' => 'balance']) }}">
                                <i class="bi bi-credit-card-2-front-fill"></i>
                                {{ __('client.services.renewbtn') }}
                            </a>
                        @else
                            <a class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3" href="{{ route('front.services.renewal', ['service' => $service]) }}">
                                <i class="bi bi-credit-card-2-front-fill"></i>
                                {{ __('client.services.managerenew') }}
                            </a>


                            <div class="hs-dropdown">

                                <button class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3 w-full">
                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                                    {{ __('client.services.renewbtn') }}
                                    <svg class="hs-dropdown-open:rotate-180 w-4 h-4 ml-auto" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>

                                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[15rem] bg-white shadow-md rounded-lg dark:bg-gray-800 dark:border dark:border-gray-700 dark:divide-gray-700 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full" aria-labelledby="hs-dropdown-default">
                                    @foreach ($gateways as $gateway)
                                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-indigo-100 focus:outline-none focus:bg-indigo-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:bg-gray-700" href="{{ route('front.services.renew', ['service' => $service, 'gateway' => $gateway->uuid]) }}">
                                            {{ $gateway->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <a href="{{ route('front.services.show', ['service' => $service]) }}" class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                        {{ __('client.services.managebtn') }}
                    </a>

                    <a class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3" href="{{ route('front.services.options', ['service' => $service]) }}">
                        <i class="bi bi-boxes"></i>
                        {{ __('client.services.manageoptions') }}
                    </a>
                    @if (auth('admin')->check())

                        <a href="{{ route('admin.services.show', ['service' => $service]) }}" class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3 text-primary">
                            <i class="bi bi-box"></i>
                            {{ __('client.services.manageserviceonadmin') }}
                        </a>
                    @endif
                    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
                        <div class="p-4 pb-0 md:p-5 md:pb-2 flex gap-x-4">
                            <div>
                                <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>

                                </div>
                            </div>
                            <div class="grow">
                                <div class="flex items-center gap-x-2">


                                    <p class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ $service->name }}
                                    </p>
                                    <x-badge-state state="{{ $service->status }}" class="mt-1"></x-badge-state>
                                </div>

                                @if (!empty($service->description))
                                    <p class="text-xs block font-medium text-gray-800 dark:text-gray-600">
                                        {{ $service->description }}
                                    </p>
                                @endif

                            </div>
                        </div>
                        @if ($service->canRenew())

                        @endif
                    </div>
                    @if ($service->server_id != null)
                        <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                            <div class="p-4 md:p-5 flex gap-x-4">
                                <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>

                                <div class="grow">
                                    <div class="flex items-center gap-x-2">
                                        <p class="text-xs uppercase tracking-wide text-gray-500">
                                            {{ __('global.server') }}
                                        </p>
                                    </div>
                                    <div class="mt-1 flex items-center gap-x-2">
                                        <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                            {{ $service->server->name }}
                                        </h3>
                                    </div>

                                    @if (!empty($service->description))
                                        <div class="mt-1 flex items-center gap-x-2">
                                            <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                                                {{ $service->description }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($service->expires_at != null)

                        <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                            <div class="p-4 md:p-5 flex gap-x-4">
                                <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400"xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                                </div>
                                <div class="grow">
                                    <div class="flex items-center gap-x-2">
                                        <p class="text-xs uppercase tracking-wide text-gray-500">
                                            {{ __('client.services.expire_date') }}
                                        </p>
                                    </div>
                                    <div class="mt-1 flex items-center gap-x-2">
                                        <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                            <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}"></x-service-days-remaining>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                        <div class="p-4 md:p-5 flex gap-x-4">
                            <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                                <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>

                            <div class="grow">
                                <div class="flex items-center gap-x-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ __('store.price') }}
                                    </p>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2">
                                    <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                        {{ formatted_price($service->getBillingPrice()->price, $service->currency) }}
                                        <span class="text-muted">/{{ $service->recurring()['unit'] }}</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                    <div class="p-4 md:p-5 flex gap-x-4">
                        <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>

                        <div class="grow">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ __('client.services.subusers.index') }}
                    </p>
                </div>
                <div class="mt-1 flex items-center gap-x-2">
                    <a href="#" class="btn-action-with-icon">{{ __('client.services.subusers.manage') }}</a>
                            </div>
                        </div>
                    </div>
                </div>-->
                </div>
            </div>
        </div>

@endsection
