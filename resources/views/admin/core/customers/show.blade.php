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
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->fullname]))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <form class="card" method="POST" action="{{ route($routePath .'.update', ['customer' => $item]) }}">
                        <div class="card-heading">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            @method('PUT')
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->fullname]) }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y')]) }}
                                </p>

                                @if ($item->isBlocked())

                                    <div class="alert bg-primary-light text-primary mt-2" role="alert">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                        {{ $item->getBlockedMessage() }}
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 flex items-center space-x-1 sm:mt-0">
                                @if (staff_has_permission('admin.manage_customers'))
                                    <button class="btn btn-primary">
                                        <i class="bi bi-save2 md:hidden"></i>
                                        <span class="hidden md:inline-flex">
                                    {{ __('admin.updatedetails') }}
                                    </span>
                                    </button>

                                    <div class="hs-dropdown relative inline-flex">
                                        <button id="hs-dropdown-with-title" type="button" class="hs-dropdown-toggle text-center inline-flex items-center gap-x-2 text-sm font-medium btn-secondary btn-sm w-full focus:outline-none disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                            {{ __('global.actions') }}
                                            <i class="bi bi-caret-down-fill hs-dropdown-open:rotate-180"></i>
                                        </button>

                                        <div style="z-index: 10000" class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 divide-y divide-gray-200 dark:bg-gray-800 dark:border dark:border-neutral-700 dark:divide-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-with-title">
                                            <div class="p-1 space-y-0.5">
                                                @if (staff_has_permission('admin.show_metadata'))

                                                    <span class="block pt-2 pb-1 px-3 text-xs font-medium uppercase text-gray-400 dark:text-neutral-500">
                                                    {{ __('admin.metadata.title') }}
                                                  </span>
                                                    <a class="flex items center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="#" data-hs-overlay="#metadata-overlay">
                                                        <i class="bi bi-database mr-2"></i>
                                                        {{ __('admin.metadata.title') }}
                                                    </a>
                                                @endif
                                                <span class="block pt-2 pb-1 px-3 text-xs font-medium uppercase text-gray-400 dark:text-neutral-500">
                                                    {{ __('global.actions') }}
                                              </span>
                                                @if (staff_has_permission('admin.autologin_customer'))

                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route($routePath . '.autologin', ['customer' => $item]) }}">
                                                        <i class="bi bi-person-circle"></i>
                                                        {{ __($translatePrefix . '.autologin.btn') }}
                                                    </a>
                                                @endif
                                                @if ($item->email_verified_at == null)
                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route($routePath . '.resend_confirmation', ['customer' => $item]) }}">
                                                        <i class="bi bi-send"></i>
                                                        {{ __($translatePrefix . '.show.resend_confirm') }}
                                                    </a>

                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route($routePath . '.confirm', ['customer' => $item]) }}">
                                                        <i class="bi bi-person-check-fill"></i>
                                                        {{ __($translatePrefix . '.show.confirm') }}
                                                    </a>
                                                @endif
                                                @if (staff_has_permission('admin.manage_services'))
                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route('admin.services.create') }}?customer_id={{ $item->id }}">
                                                        <i class="bi bi-box2"></i>
                                                        {{ __($translatePrefix . '.show.create_service') }}
                                                    </a>
                                                @endif
                                                @if (staff_has_permission('admin.manage_invoices'))
                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route('admin.invoices.create') }}?customer_id={{ $item->id }}">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                        {{ __($translatePrefix . '.show.create_invoice') }}
                                                    </a>
                                                @endif
                                                @if (staff_has_permission('admin.manage_tickets'))
                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route('admin.helpdesk.tickets.create') }}?customer_id={{ $item->id }}">
                                                        <i class="bi bi-chat-left-text"></i>
                                                        {{ __($translatePrefix . '.show.create_ticket') }}
                                                    </a>
                                                @endif
                                                @if (staff_has_permission('admin.send_emails'))
                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route('admin.emails.create') }}?emails={{ $item->email }}">

                                                        <i class="bi bi-envelope-plus"></i>
                                                        {{ __($translatePrefix . '.show.send_email') }}
                                                    </a>
                                                @endif

                                                <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-gray-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route($routePath . '.send_password', ['customer' => $item]) }}">
                                                    <i class="bi bi-send"></i>
                                                    {{ __($translatePrefix . '.show.send_password') }}
                                                </a>
                                                <span class="block pt-2 pb-1 px-3 text-xs font-medium uppercase text-gray-400 dark:text-neutral-500">
        DANGER ZONE
      </span>
                                                @if ($item->isBanned() || $item->isSuspended())

                                                    <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-green-500 hover:bg-green-100 focus:outline-none focus:bg-green-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-red-300 dark:focus:bg-neutral-700" href="#" data-hs-overlay="#suspend-overlay">
                                                        <i class="bi bi-person-exclamation"></i>
                                                        {{ __($translatePrefix . '.show.reactivate') }}
                                                    </a>
                                                @else
                                                    @if (!$item->isSuspended())
                                                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-orange-500 hover:bg-orange-100 focus:outline-none focus:bg-orange-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-orange-300 dark:focus:bg-neutral-700" href="#" data-hs-overlay="#suspend-overlay">
                                                            <i class="bi bi-person-exclamation"></i>
                                                            {{ __($translatePrefix . '.show.suspend') }}
                                                        </a>
                                                    @endif
                                                    @if (!$item->isBanned())
                                                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-500 hover:bg-red-100 focus:outline-none focus:bg-red-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-red-300 dark:focus:bg-red-700" href="#" data-hs-overlay="#ban-overlay">
                                                            <i class="bi bi-person-fill-slash"></i>
                                                            {{ __($translatePrefix . '.show.ban') }}
                                                        </a>
                                                    @endif
                                                    @if ($item->twoFactorEnabled())
                                                        <button type="button" id="disabled2faButton" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-500 hover:bg-red-100 focus:outline-none focus:bg-red-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-red-300 dark:focus:bg-red-700">
                                                            <i class="bi bi-shield-lock-fill"></i>
                                                            {{ __($translatePrefix.'.show.disable2fa') }}
                                                        </button>
                                                    @endif
                                                    <button type="button" id="deleteButton" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-500 hover:bg-red-100 focus:outline-none focus:bg-red-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-red-300 dark:focus:bg-red-700">
                                                        <i class="bi bi-trash"></i>
                                                        {{ __('global.delete') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-2 grid-cols-1">
                            <div>
                                <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix. '.show.billing')  }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="sm:col-span-3">
                                        @include('admin/shared/input', ['name' => 'email', 'label' => __('global.email'), 'value' => old('email', $item->email), 'type' => 'email'])
                                    </div>
                                    <div class="sm:col-span-2">
                                        @include("admin/shared/input", ["name" => "address", "label" => __('global.address'), 'value' => old('address', $item->address)])
                                    </div>
                                    <div class="sm:col-span-1">
                                        @include("admin/shared/input", ["name" => "address2", "label" => __('global.address2'), 'value' => old('address2', $item->address2)])
                                    </div>

                                    <div>
                                        @include("admin/shared/input", ["name" => "zipcode", "label" => __('global.zip'), 'value' => old('zipcode', $item->zipcode)])
                                    </div>
                                    <div>
                                        @include("admin/shared/input", ["name" => "phone", "label" => __('global.phone'), 'value' => old('phone', $item->phone)])
                                    </div>

                                    <div>
                                        @include("admin/shared/select", ["name" => "locale", "label" => __('global.locale'), 'value' => old('locale', $item->locale), 'options' => $locales])
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix. '.show.details') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        @include('admin/shared/input', ['name' => 'last_login', 'label' => __($translatePrefix. '.show.last_login'), 'value' => old('last_login', $item->last_login), 'disabled' => true])
                                        @include("admin/shared/textarea", ["name" => "billing_details", "label" => __('global.billing_details'), 'value' => old('billing_details', $item->billing_details), 'help' => __('global.billing_details_help')])

                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'last_ip', 'label' => __($translatePrefix. '.show.last_ip'), 'value' => old('last_ip', $item->last_ip), 'disabled' => true])
                                        @include('admin/shared/textarea', ['name' => 'notes', 'label' => __($translatePrefix. '.show.notes'), 'value' => old('notes', $item->notes)])
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h5 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('client.profile.security.index') }}</h5>
                                    @include('admin/shared/password', ['name' => 'password', 'label' => __('global.password'), 'value' => old('password'), 'help' => __('admin.customers.show.passwordhelp')])
                                </div>
                                @if ($item->email_verified_at == null)

                                    <div>
                                        <label for="confirmation_url" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{  __($translatePrefix. '.show.url.confirmation') }}</label>

                                        <div class="flex rounded-lg shadow-sm mt-2">
                                            <input type="text" readonly class="input-text" id="confirmation_url" value="{{ $item->getConfirmationUrl() }}">
                                            <button type="button" data-clipboard-target="#confirmation_url" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                                <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                                <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                @if (staff_has_permission('admin.show_invoices'))

                                    <div class="grid grid-cols-3 mt-2 lg:items-center border border-gray-200 rounded-xl dark:border-gray-700">
                                        <div class="flex flex-col p-4">
                                            <h4 class="text-gray-800 mb-1 dark:text-gray-200">{{ __($translatePrefix . '.show.stats.paid')  }}</h4>
                                            <div class="flex gap-x-1">
                                                <span class="text-xl font-normal text-gray-800 dark:text-gray-200">{{ currency_symbol() }}</span>
                                                <p class="text-gray-800 font-semibold text-3xl dark:text-gray-200">
                                                    {{ $item->invoices->where('status', 'paid')->sum('total')  }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col p-4">
                                            <div class="flex justify-between">
                                                <h4 class="text-gray-800 mb-1 dark:text-gray-200">{{ __($translatePrefix . '.show.stats.unpaid')  }}</h4>
                                            </div>
                                            <div class="flex gap-x-1">
                                                <span class="text-xl font-normal text-gray-800 dark:text-gray-200">{{ currency_symbol() }}</span>
                                                <p class="text-gray-800 font-semibold text-3xl dark:text-gray-200">
                                                    {{ $item->invoices->where('status', 'unpaid')->sum('total') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col p-4">
                                            <h4 class="text-gray-800 mb-1 dark:text-gray-200">{{ __($translatePrefix . '.show.stats.services')  }}</h4>
                                            <div class="flex gap-x-1">
                                                <span class="text-xl font-normal text-gray-800 dark:text-gray-200">{{ currency_symbol() }}</span>
                                                <p class="text-gray-800 font-semibold text-3xl dark:text-gray-200">
                                                    {{ $item->services->sum('price') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @if (app('extension')->extensionIsEnabled('supportid'))
                                @include('supportid_admin::customer/card', ['customer' => $item])
                            @endif
                            </div>
                        </div>

                    </form>
                    <div class="grid lg:grid-cols-2 gap-2 grid-cols-1">
                        @if (staff_has_permission('admin.show_services'))
                            @include('admin/core/customers/cards/services', ['services' => $services])
                        @endif
                        @if (staff_has_permission('admin.show_invoices'))
                            @include('admin/core/customers/cards/invoices', ['invoices' => $invoices])
                        @endif
                        @if (staff_has_permission('admin.show_emails'))
                            @include('admin/core/customers/cards/emails', ['emails' => $emails])
                        @endif
                        @if (staff_has_permission('admin.manage_tickets'))
                            @include('admin/core/customers/cards/tickets', ['tickets' => $tickets])
                        @endif

                        @if (staff_has_permission('admin.show_logs'))
                            <div class="card">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __($translatePrefix . '.show.history') }}</h4>
                                @include('admin/core/actionslog/usertable', ['logs' => $logs])
                            </div>
                        @endif

                        @if (staff_has_permission('admin.show_payment_methods'))
                            @include('admin/core/customers/cards/payment-methods', ['paymentmethods' => $paymentmethods])
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @include('admin/metadata/overlay', ['item' => $item])
        @if (staff_has_permission('admin.manage_customers'))

            <div id="suspend-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
                <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        @if (!$item->isBlocked())
                            {{ __($translatePrefix . '.show.suspend') }}
                        @else
                            {{ __($translatePrefix . '.show.reactivate') }}
                        @endif
                    </h3>
                    <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#suspend-overlay">
                        <span class="sr-only">{{ __('global.closemodal') }}</span>
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="p-4">

                    <form method="POST" action="{{ route('admin.customers.action', ['customer' => $item, 'action' => $item->isBlocked() ? 'reactivate' : 'suspend']) }}">
                        <p class="text-gray-800 dark:text-gray-400">
                        @csrf
                        @if (!$item->isBlocked())
                            @include('admin/shared/textarea', ['name' => 'reason', 'label' => __('provisioning.admin.services.suspend.reason'), 'value' => old('reason', $item->getMetadata('suspended_reason'))])
                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.suspend_services')])
                            </div>

                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                            </div>
                        @elseif ($item->isBlocked())
                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.unsuspend_services'), 'value' => true])
                            </div>

                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                            </div>
                        @endif
                        @if (!$item->isBlocked())
                            <button class="btn btn-warning w-full mt-3"> <i class="bi bi-person-exclamation mr-2"></i>  {{ __($translatePrefix . '.show.suspend') }}</button>
                        @else
                            <button class="btn btn-success w-full mt-3"> <i class="bi bi-person-person-check-fill mr-2"></i>  {{ __($translatePrefix . '.show.reactivate') }}</button>
                            @endif
                            </p>
                    </form>
                </div>
            </div>

            <div id="ban-overlay" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
                <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        @if (!$item->isBanned())
                            {{ __($translatePrefix . '.show.ban') }}
                        @else
                            {{ __($translatePrefix . '.show.reactivate') }}
                        @endif
                    </h3>
                    <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#suspend-overlay">
                        <span class="sr-only">{{ __('global.closemodal') }}</span>
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="p-4">

                    <form method="POST" action="{{ route('admin.customers.action', ['customer' => $item, 'action' => $item->isBlocked() ? 'reactivate' : 'ban']) }}">
                        <p class="text-gray-800 dark:text-gray-400">

                        @csrf
                        @if (!$item->isSuspended())
                            @include('admin/shared/textarea', ['name' => 'reason', 'label' => __($translatePrefix . '.show.reason'), 'value' => old('reason', $item->getMetadata('banned_reason'))])
                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.expire_services')])
                            </div>

                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                            </div>
                        @elseif ($item->getMetadata('suspended_at') != null)
                            @include('admin/shared/textarea', ['name' => 'reason', 'label' => __($translatePrefix . '.show.reason'), 'value' => $item->getMetadata('banned_reason'), 'disabled' => true])
                            @include('admin/shared/input', ['name' => 'suspend_at', 'label' => __('provisioning.admin.services.suspend.suspend_at'), 'disabled' => true,'value' => $item->getMetadata('banned_at')])
                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'force', 'label' => __($translatePrefix . '.show.unsuspend_service')])
                            </div>
                            <div class="mt-2">
                                @include('admin/shared/checkbox', ['name' => 'notify', 'label' => __('provisioning.admin.services.suspend.notify')])
                            </div>
                        @endif
                        @if (!$item->isSuspended())
                            <button class="btn btn-danger w-full mt-3"> <i class="bi bi-person-fill-slash mr-2"></i>  {{ __($translatePrefix . '.show.ban') }}</button>
                        @else
                            <button class="btn btn-success w-full mt-3"> <i class="bi bi-person-person-check-fill mr-2"></i>  {{ __($translatePrefix . '.show.reactivate') }}</button>
                            @endif
                            </p>
                    </form>
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route($routePath . '.destroy', ['customer' => $item]) }}" id="deleteForm">
            @csrf
            @method('DELETE')
        </form>
        @if ($item->twoFactorEnabled())
            <form method="POST" action="{{ route($routePath . '.action', ['customer' => $item, 'action' => 'disable2FA']) }}" id="disable2faForm">
                @csrf
            </form>
        @endif
        <script>
            document.getElementById('deleteButton').addEventListener('click', function() {
                document.getElementById('deleteForm').submit();
            });
            @if ($item->twoFactorEnabled())
            document.getElementById('disabled2faButton').addEventListener('click', function() {
                document.getElementById('disable2faForm').submit();
            });
            @endif
        </script>
        @endsection
