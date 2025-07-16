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
@section('title', __('global.clientarea'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/filter.js') }}"></script>
@endsection
@section('content')
    <!-- Card Section -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include("shared.alerts")

        <!-- Grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Card -->
            <div class="flex flex-col bg-white shadow-sm rounded-xl dark:bg-gray-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex dark:bg-slate-900 justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">

                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"/></svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                {{ __('global.invoices')  }}
                            </p>
                            @if ($pending != 0)

                            <div class="hs-tooltip">
                                <div class="hs-tooltip-toggle">
                                    <svg class="flex-shrink-0 w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-slate-700" role="tooltip">
                                  {{ __('client.pending_invoices', ['count' => $pending]) }}

                </span>
                                </div>
                            </div>
                                @endif
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
                                {{ $invoicesCount }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white shadow-sm rounded-xl dark:bg-gray-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex dark:bg-slate-900 justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                {{ __('global.services')  }}
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                {{ $servicesCount }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white shadow-sm rounded-xl dark:bg-gray-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex dark:bg-slate-900 justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
                    <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                {{ __('global.balance') }}
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
                                {{ formatted_price(auth()->user()->balance) }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white shadow-sm rounded-xl dark:bg-gray-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex dark:bg-slate-900 justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 12H3M12 3v18"/></svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                {{ __('global.tickets') }}
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                {{ $ticketsCount }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->
        </div>
        <!-- End Grid -->
        <div class="grid md:grid-cols-12 grid-cols-4 gap-2">
            <div class="md:col-span-8 mt-4 col-span-12 mt-4">
                <div class="grid grid-cols-1 gap-2">
                    @include('front/provisioning/services/card', ['services' => $services, 'count' => $servicesCount, 'filters' => $serviceFilters, 'filter' => null])
                    @include('front/billing/invoices/card', ['invoices' => $invoices, 'count' => $invoicesCount, 'filters' => $serviceFilters, 'filter' => null])
                    @include('front/helpdesk/support/card', ['tickets' => $tickets, 'count' => $ticketsCount, 'filters' => $serviceFilters, 'filter' => null])
                </div>
            </div>
            <div class="md:col-span-4 col-span-1 mt-4">
                @if (app('extension')->extensionIsEnabled('discordlink'))
                    @include('discordlink::front/client/discord')
                @endif
            </div>
        </div>
    </div>
    <!-- End Card Section -->

@endsection
