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
@section('title', __($translatePrefix . '.show.title', ['name' => $item->fullname]))
@section('scripts')
<script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
<script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
@endsection
@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')
    <div class="card mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-16 h-16 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white text-2xl font-bold">
                    {{ $item->initials() }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ $item->fullname }}
                        @if($item->company_name)
                        <span class="text-sm font-normal text-gray-500">- {{ $item->company_name }}</span>
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="bi bi-envelope mr-1"></i>{{ $item->email }}
                        @if($item->phone)
                        <span class="mx-2">|</span>
                        <i class="bi bi-telephone mr-1"></i>{{ $item->phone }}
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/Y')]) }}
                    </p>
                    @if ($item->isBlocked())
                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $item->getBlockedMessage() }}
                    </span>
                    @endif
                </div>
            </div>

            @if (staff_has_permission('admin.show_invoices'))
            <div class="flex gap-4">
                <div class="text-center px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatted_price($item->invoices->where('status', 'paid')->sum('total')) }}</p>
                    <p class="text-xs text-gray-500">{{ __($translatePrefix . '.show.stats.paid') }}</p>
                </div>
                <div class="text-center px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatted_price($item->invoices->where('status', 'pending')->sum('total')) }}</p>
                    <p class="text-xs text-gray-500">{{ __($translatePrefix . '.show.stats.unpaid') }}</p>
                </div>
                <div class="text-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatted_price($item->balance) }}</p>
                    <p class="text-xs text-gray-500">{{ __('global.balance') }}</p>
                </div>
            </div>
            @endif
            @if (staff_has_permission('admin.manage_customers'))
            <div class="flex items-center gap-2">
                <div class="hs-dropdown relative inline-flex">
                    <button id="hs-dropdown-with-title" type="button" class="hs-dropdown-toggle btn btn-secondary" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                        {{ __('global.actions') }}
                        <i class="bi bi-caret-down-fill hs-dropdown-open:rotate-180 ml-1"></i>
                    </button>

                    <div style="z-index: 10000" class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 divide-y divide-gray-200 dark:bg-gray-800 dark:border dark:border-neutral-700 dark:divide-neutral-700" role="menu">
                        <div class="p-1 space-y-0.5">
                            @if (staff_has_permission('admin.show_metadata'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="#" data-hs-overlay="#metadata-overlay">
                                <i class="bi bi-database"></i>{{ __('admin.metadata.title') }}
                            </a>
                            @endif
                            @if (staff_has_permission('admin.autologin_customer'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route($routePath . '.autologin', ['customer' => $item]) }}">
                                <i class="bi bi-person-circle"></i>{{ __($translatePrefix . '.autologin.btn') }}
                            </a>
                            @endif
                            @if ($item->email_verified_at == null)
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route($routePath . '.resend_confirmation', ['customer' => $item]) }}">
                                <i class="bi bi-send"></i>{{ __($translatePrefix . '.show.resend_confirm') }}
                            </a>
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route($routePath . '.confirm', ['customer' => $item]) }}">
                                <i class="bi bi-person-check-fill"></i>{{ __($translatePrefix . '.show.confirm') }}
                            </a>
                            @endif
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route($routePath . '.send_password', ['customer' => $item]) }}">
                                <i class="bi bi-key"></i>{{ __($translatePrefix . '.show.send_password') }}
                            </a>
                        </div>
                        <div class="p-1 space-y-0.5">
                            <span class="block pt-2 pb-1 px-3 text-xs font-medium uppercase text-gray-400">{{ __('global.create') }}</span>
                            @if (staff_has_permission('admin.manage_services'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route('admin.services.create') }}?customer_id={{ $item->id }}">
                                <i class="bi bi-box2"></i>{{ __($translatePrefix . '.show.create_service') }}
                            </a>
                            @endif
                            @if (staff_has_permission('admin.manage_invoices'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route('admin.invoices.create') }}?customer_id={{ $item->id }}">
                                <i class="bi bi-file-earmark-text"></i>{{ __($translatePrefix . '.show.create_invoice') }}
                            </a>
                            @endif
                            @if (staff_has_permission('admin.manage_tickets'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route('admin.helpdesk.tickets.create') }}?customer_id={{ $item->id }}">
                                <i class="bi bi-chat-left-text"></i>{{ __($translatePrefix . '.show.create_ticket') }}
                            </a>
                            @endif
                            @if (staff_has_permission('admin.send_emails'))
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700" href="{{ route('admin.emails.create') }}?emails={{ $item->email }}">
                                <i class="bi bi-envelope-plus"></i>{{ __($translatePrefix . '.show.send_email') }}
                            </a>
                            @endif
                        </div>
                        <div class="p-1 space-y-0.5">
                            <span class="block pt-2 pb-1 px-3 text-xs font-medium uppercase text-red-400">DANGER ZONE</span>
                            @if ($item->isBanned() || $item->isSuspended())
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30" href="#" data-hs-overlay="#suspend-overlay">
                                <i class="bi bi-person-check"></i>{{ __($translatePrefix . '.show.reactivate') }}
                            </a>
                            @else
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-900/30" href="#" data-hs-overlay="#suspend-overlay">
                                <i class="bi bi-person-exclamation"></i>{{ __($translatePrefix . '.show.suspend') }}
                            </a>
                            <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30" href="#" data-hs-overlay="#ban-overlay">
                                <i class="bi bi-person-fill-slash"></i>{{ __($translatePrefix . '.show.ban') }}
                            </a>
                            @endif
                            @if ($item->twoFactorEnabled())
                            <button type="button" id="disabled2faButton" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30">
                                <i class="bi bi-shield-lock-fill"></i>{{ __($translatePrefix.'.show.disable2fa') }}
                            </button>
                            @endif
                            <button type="button" id="deleteButton" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30">
                                <i class="bi bi-trash"></i>{{ __('global.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4">
        <div class="lg:w-64 flex-shrink-0">
            <div class="card sticky top-4 p-1!important">
                <nav class="flex flex-col gap-1" aria-label="Tabs" role="tablist" aria-orientation="vertical">
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg active" id="tabs-info-item" aria-selected="true" data-hs-tab="#tabs-info" aria-controls="tabs-info" role="tab">
                        <i class="bi bi-person-vcard text-lg"></i>
                        {{ __($translatePrefix . '.show.details') }}
                    </button>
                    @if (staff_has_permission('admin.show_services'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-services-item" aria-selected="false" data-hs-tab="#tabs-services" aria-controls="tabs-services" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-box2 text-lg"></i>
                            {{ __($translatePrefix . '.show.services') }}
                        </span>
                        @if ($services->total() > 0)
                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $services->total() }}</span>
                        @endif
                    </button>
                    @endif
                    @if (staff_has_permission('admin.show_invoices'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-invoices-item" aria-selected="false" data-hs-tab="#tabs-invoices" aria-controls="tabs-invoices" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-file-earmark-text text-lg"></i>
                            {{ __($translatePrefix . '.show.invoices') }}
                        </span>
                        @if ($invoices->total() > 0)
                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $invoices->total() }}</span>
                        @endif
                    </button>
                    @endif
                    @if (staff_has_permission('admin.manage_tickets'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-tickets-item" aria-selected="false" data-hs-tab="#tabs-tickets" aria-controls="tabs-tickets" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-chat-left-text text-lg"></i>
                            {{ __($translatePrefix . '.show.tickets') }}
                        </span>
                        @if ($tickets->total() > 0)
                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $tickets->total() }}</span>
                        @endif
                    </button>
                    @endif
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-notes-item" aria-selected="false" data-hs-tab="#tabs-notes" aria-controls="tabs-notes" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-sticky text-lg"></i>
                            {{ __($translatePrefix . '.show.notes') }}
                        </span>
                        @if ($customerNotes->count() > 0)
                        <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $customerNotes->count() }}</span>
                        @endif
                    </button>
                    @if (staff_has_permission('admin.show_emails'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-emails-item" aria-selected="false" data-hs-tab="#tabs-emails" aria-controls="tabs-emails" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-envelope text-lg"></i>
                            {{ __($translatePrefix . '.show.emails') }}
                        </span>
                    </button>
                    @endif
                    @if (staff_has_permission('admin.show_payment_methods'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center justify-between gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-payment-item" aria-selected="false" data-hs-tab="#tabs-payment" aria-controls="tabs-payment" role="tab">
                        <span class="inline-flex items-center gap-x-3">
                            <i class="bi bi-credit-card text-lg"></i>
                            {{ __($translatePrefix . '.show.payment-methods') }}
                        </span>
                    </button>
                    @endif
                    @if (staff_has_permission('admin.show_logs'))
                    <button type="button" class="hs-tab-active:bg-primary/10 hs-tab-active:text-primary hs-tab-active:border-l-primary py-3 px-4 inline-flex items-center gap-x-3 border-l-2 border-transparent text-sm text-gray-600 hover:text-primary hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 focus:outline-none rounded-r-lg" id="tabs-logs-item" aria-selected="false" data-hs-tab="#tabs-logs" aria-controls="tabs-logs" role="tab">
                        <i class="bi bi-clock-history text-lg"></i>
                        {{ __($translatePrefix . '.show.history') }}
                    </button>
                    @endif
                </nav>
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <div id="tabs-info" role="tabpanel" aria-labelledby="tabs-info-item">
                <form class="card" method="POST" action="{{ route($routePath .'.update', ['customer' => $item]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    @method('PUT')

                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __($translatePrefix. '.show.billing') }}</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    @include('admin/shared/input', ['name' => 'firstname', 'label' => __('global.firstname'), 'value' => old('firstname', $item->firstname)])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'lastname', 'label' => __('global.lastname'), 'value' => old('lastname', $item->lastname)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "company_name", "label" => __('global.company_name'), 'value' => old('company_name', $item->company_name)])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'balance', 'label' => __('global.balance'), 'value' => old('balance', $item->balance), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 mt-4">
                                <div>
                                    @include('admin/shared/input', ['name' => 'email', 'label' => __('global.email'), 'value' => old('email', $item->email), 'type' => 'email'])
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mt-4">
                                <div>
                                    @include("admin/shared/input", ["name" => "address", "label" => __('global.address'), 'value' => old('address', $item->address)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "address2", "label" => __('global.address2'), 'value' => old('address2', $item->address2)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "zipcode", "label" => __('global.zip'), 'value' => old('zipcode', $item->zipcode)])
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mt-4">
                                <div>
                                    @include("admin/shared/select", ["name" => "country", "label" => __('global.country'), "options" => $countries, "value" => old('country', $item->country)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "city", "label" => __('global.city'), 'value' => old('city', $item->city)])
                                </div>
                                <div>
                                    @include("admin/shared/input", ["name" => "region", "label" => __('global.region'), 'value' => old('region', $item->region)])
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    @include("admin/shared/input", ["name" => "phone", "label" => __('global.phone'), 'value' => old('phone', $item->phone)])
                                </div>
                                <div>
                                    @include("admin/shared/select", ["name" => "locale", "label" => __('global.locale'), 'value' => old('locale', $item->locale), 'options' => $locales])
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __($translatePrefix. '.show.details') }}</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    @include('admin/shared/input', ['name' => 'last_login', 'label' => __($translatePrefix. '.show.last_login'), 'value' => old('last_login', $item->last_login), 'disabled' => true])
                                </div>
                                <div>
                                    @include('admin/shared/input', ['name' => 'last_ip', 'label' => __($translatePrefix. '.show.last_ip'), 'value' => old('last_ip', $item->last_ip), 'disabled' => true])
                                </div>
                            </div>
                            <div class="mt-4">
                                <div>
                                    @include("admin/shared/textarea", ["name" => "billing_details", "label" => __('global.billing_details'), 'value' => old('billing_details', $item->billing_details), 'help' => __('global.billing_details_help')])
                                </div>
                            </div>

                            <h5 class="text-md font-semibold text-gray-800 dark:text-gray-200 mt-6 mb-2">{{ __('client.profile.security.index') }}</h5>
                            <div>
                                @include('admin/shared/password', ['name' => 'password', 'label' => __('global.password'), 'value' => old('password'), 'help' => __('admin.customers.show.passwordhelp')])
                            </div>

                            @if ($item->email_verified_at == null)
                            <div class="mt-4">
                                <label for="confirmation_url" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">{{ __($translatePrefix. '.show.url.confirmation') }}</label>
                                <div class="flex rounded-lg shadow-sm mt-2">
                                    <input type="text" readonly class="input-text" id="confirmation_url" value="{{ $item->getConfirmationUrl() }}">
                                    <button type="button" data-clipboard-target="#confirmation_url" data-clipboard-action="copy" class="js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                        <i class="bi bi-clipboard js-clipboard-default"></i>
                                        <i class="bi bi-check js-clipboard-success hidden"></i>
                                    </button>
                                </div>
                            </div>
                            @endif


                        </div>
                    </div>

                    @if (staff_has_permission('admin.manage_customers'))
                    <div class="mt-4">
                        <button class="btn btn-primary">
                            <i class="bi bi-save2 mr-2"></i>{{ __('admin.updatedetails') }}
                        </button>
                    </div>
                    @endif
                </form>
            </div>

            @if (staff_has_permission('admin.show_services'))
            <div id="tabs-services" class="hidden" role="tabpanel" aria-labelledby="tabs-services-item">
                @include('admin/core/customers/cards/services', ['services' => $services])
            </div>
            @endif

            @if (staff_has_permission('admin.show_invoices'))
            <div id="tabs-invoices" class="hidden" role="tabpanel" aria-labelledby="tabs-invoices-item">
                @include('admin/core/customers/cards/invoices', ['invoices' => $invoices])
            </div>
            @endif

            @if (staff_has_permission('admin.manage_tickets'))
            <div id="tabs-tickets" class="hidden" role="tabpanel" aria-labelledby="tabs-tickets-item">
                @include('admin/core/customers/cards/tickets', ['tickets' => $tickets])
            </div>
            @endif

            <div id="tabs-notes" class="hidden" role="tabpanel" aria-labelledby="tabs-notes-item">
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __($translatePrefix. '.show.notes') }}</h3>
                    </div>

                    <div class="mb-6">
                        <div class="flex gap-3">
                            <textarea form="addNoteForm" name="content" rows="2" class="input-text flex-1" placeholder="{{ __($translatePrefix. '.show.notes_placeholder') }}"></textarea>
                            <button type="submit" form="addNoteForm" class="btn btn-primary h-fit self-end">
                                <i class="bi bi-plus-lg mr-1"></i>{{ __('global.add') }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @forelse($customerNotes as $note)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border-l-4 border-primary">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-sm font-medium">
                                        {{ $note->author ? substr($note->author->username, 0, 2) : '?' }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $note->authorName }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="bi bi-calendar3 mr-1"></i>{{ $note->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap pl-10">{{ $note->content }}</p>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <i class="bi bi-sticky text-4xl text-gray-300 dark:text-gray-600"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ __($translatePrefix. '.show.no_notes') }}</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if (staff_has_permission('admin.show_emails'))
            <div id="tabs-emails" class="hidden" role="tabpanel" aria-labelledby="tabs-emails-item">
                @include('admin/core/customers/cards/emails', ['emails' => $emails])
            </div>
            @endif

            @if (staff_has_permission('admin.show_payment_methods'))
            <div id="tabs-payment" class="hidden" role="tabpanel" aria-labelledby="tabs-payment-item">
                @include('admin/core/customers/cards/payment-methods', ['paymentmethods' => $paymentmethods])
            </div>
            @endif

            @if (staff_has_permission('admin.show_logs'))
            <div id="tabs-logs" class="hidden" role="tabpanel" aria-labelledby="tabs-logs-item">
                <div class="card">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __($translatePrefix . '.show.history') }}</h4>
                    @include('admin/core/actionslog/usertable', ['logs' => $logs])
                </div>
            </div>
            @endif
        </div>
    </div>

    @include('admin/metadata/overlay', ['item' => $item])

    @if (staff_has_permission('admin.manage_customers'))
    <div id="suspend-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                @if (!$item->isBlocked())
                {{ __($translatePrefix . '.show.suspend') }}
                @else
                {{ __($translatePrefix . '.show.reactivate') }}
                @endif
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#suspend-overlay">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route('admin.customers.action', ['customer' => $item, 'action' => $item->isBlocked() ? 'reactivate' : 'suspend']) }}">
                @csrf
                @if (!$item->isBlocked())
                @include('admin/shared/textarea', ['name' => 'reason', 'label' => __('provisioning.admin.services.suspend.reason'), 'value' => old('reason', $item->getMetadata('suspended_reason'))])
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.suspend_services')])
                </div>
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                </div>
                <button class="btn btn-warning w-full mt-3"><i class="bi bi-person-exclamation mr-2"></i>{{ __($translatePrefix . '.show.suspend') }}</button>
                @else
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.unsuspend_services'), 'value' => true])
                </div>
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                </div>
                <button class="btn btn-success w-full mt-3"><i class="bi bi-person-check-fill mr-2"></i>{{ __($translatePrefix . '.show.reactivate') }}</button>
                @endif
            </form>
        </div>
    </div>
    <div id="ban-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                @if (!$item->isBanned())
                {{ __($translatePrefix . '.show.ban') }}
                @else
                {{ __($translatePrefix . '.show.reactivate') }}
                @endif
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#ban-overlay">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route('admin.customers.action', ['customer' => $item, 'action' => $item->isBlocked() ? 'reactivate' : 'ban']) }}">
                @csrf
                @if (!$item->isSuspended())
                @include('admin/shared/textarea', ['name' => 'reason', 'label' => __($translatePrefix . '.show.reason'), 'value' => old('reason', $item->getMetadata('banned_reason'))])
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.expire_services')])
                </div>
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                </div>
                <button class="btn btn-danger w-full mt-3"><i class="bi bi-person-fill-slash mr-2"></i>{{ __($translatePrefix . '.show.ban') }}</button>
                @else
                @include('admin/shared/textarea', ['name' => 'reason', 'label' => __($translatePrefix . '.show.reason'), 'value' => $item->getMetadata('banned_reason'), 'disabled' => true])
                @include('admin/shared/input', ['name' => 'suspend_at', 'label' => __('provisioning.admin.services.suspend.suspend_at'), 'disabled' => true,'value' => $item->getMetadata('banned_at')])
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.unsuspend_service')])
                </div>
                <div class="mt-2">
                    @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                </div>
                <button class="btn btn-success w-full mt-3"><i class="bi bi-person-check-fill mr-2"></i>{{ __($translatePrefix . '.show.reactivate') }}</button>
                @endif
            </form>
        </div>
    </div>
    @endif
    <form method="POST" action="{{ route($routePath . '.destroy', ['customer' => $item]) }}" id="deleteForm">
        @csrf
        @method('DELETE')
    </form>
    <form method="POST" action="{{ route('admin.customers.notes.store', ['customer' => $item]) }}" id="addNoteForm">
        @csrf
    </form>
    @if ($item->twoFactorEnabled())
    <form method="POST" action="{{ route($routePath . '.action', ['customer' => $item, 'action' => 'disable2FA']) }}" id="disable2faForm">
        @csrf
    </form>
    @endif

    @if($item->twoFactorEnabled())
    <script>
        document.getElementById('disabled2faButton').addEventListener('click', function() {
            document.getElementById('disable2faForm').submit();
        });
    </script>
    @endif

    <script>
        document.getElementById('deleteButton').addEventListener('click', function() {
            document.getElementById('deleteForm').submit();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const customerId = '{{ $item->id }}';
            const storageKey = 'customer_tab_' + customerId;
            const expiryMinutes = 10;

            function activateTab(tabId) {
                const tabButton = document.getElementById(tabId);
                if (!tabButton) return false;

                const panelId = tabButton.getAttribute('data-hs-tab');
                if (!panelId) return false;
                document.querySelectorAll('[role="tab"]').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('[role="tabpanel"]').forEach(p => {
                    p.classList.add('hidden');
                });
                tabButton.classList.add('active');
                tabButton.setAttribute('aria-selected', 'true');
                const panel = document.querySelector(panelId);
                if (panel) {
                    panel.classList.remove('hidden');
                }

                return true;
            }

            const saved = localStorage.getItem(storageKey);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    if (data.expiry > Date.now() && data.tabId) {
                        activateTab(data.tabId);
                    } else {
                        localStorage.removeItem(storageKey);
                    }
                } catch (e) {
                    localStorage.removeItem(storageKey);
                }
            }

            document.querySelectorAll('[role="tab"]').forEach(tab => {
                tab.addEventListener('click', function() {
                    localStorage.setItem(storageKey, JSON.stringify({
                        tabId: this.id,
                        expiry: Date.now() + (expiryMinutes * 60 * 1000)
                    }));
                });
            });
        });
    </script>
</div>
@endsection