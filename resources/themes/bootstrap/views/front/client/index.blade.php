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
    <script src="{{ Vite::asset('resources/themes/bootstrap/js/filter.js') }}"></script>
@endsection
@section('content')
    <!-- Card Section -->
    <div class="container px-4 py-4 mx-auto">

    @include("shared.alerts")

        <!-- Grid -->
        <div class="row g-4">
            <!-- Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="d-flex justify-content-center align-items-center bg-light rounded-circle" style="width: 46px; height: 46px;">
                            <svg class="text-muted" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
                                <path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"></path>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase text-muted small mb-1">{{ __('global.invoices') }} @if ($pending != 0)

                                <i class="bi bi-exclamation-circle text-warning"  data-bs-toggle="tooltip"
                                   data-bs-placement="top"
                                   data-bs-title="{{ __('client.pending_invoices', ['count' => $pending]) }}"></i>

                                @endif</p>

                            <h3 class="h5 mt-2 mb-0">{{ $invoicesCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="d-flex justify-content-center align-items-center bg-light rounded-circle" style="width: 46px; height: 46px;">
                            <svg class="text-muted" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                <line x1="6" y1="18" x2="6.01" y2="18"></line>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase text-muted small mb-1">{{ __('global.services') }}</p>
                            <h3 class="h5 mt-2 mb-0">{{ $servicesCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="d-flex justify-content-center align-items-center bg-light rounded-circle" style="width: 46px; height: 46px;">
                            <svg class="text-muted" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase text-muted small mb-1">{{ __('global.balance') }}</p>
                            <h3 class="h5 mt-2 mb-0">{{ formatted_price(auth()->user()->balance) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex gap-3 align-items-center">
                        <div class="d-flex justify-content-center align-items-center bg-light rounded-circle" style="width: 46px; height: 46px;">
                            <svg class="text-muted" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <path d="M21 12H3M12 3v18"></path>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase text-muted small mb-1">{{ __('global.tickets') }}</p>
                            <h3 class="h5 mt-2 mb-0">{{ $ticketsCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->
        </div>
        <!-- End Grid -->

        <div class="row g-2 mt-4">
            <div class="col-md-8">
                <div class="row g-2">
                    @include('front/provisioning/services/card', ['services' => $services, 'count' => $servicesCount, 'filters' => $serviceFilters, 'filter' => null])
                    @include('front/billing/invoices/card', ['invoices' => $invoices, 'count' => $invoicesCount, 'filters' => $serviceFilters, 'filter' => null])
                    @include('front/helpdesk/support/card', ['tickets' => $tickets, 'count' => $ticketsCount, 'filters' => $serviceFilters, 'filter' => null])
                </div>
            </div>
            <div class="col-md-4">
                @if (app('extension')->extensionIsEnabled('discordlink'))
                    @include('discordlink::front/client/discord')
                @endif
            </div>
        </div>
        <!-- End Card Section -->

    </div>
        <!-- End Card Section -->

@endsection
