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
@section('title', __('client.services.subusers.manage'))
@section('content')
    <div class="max-w-[85rem] py-5 lg:py-7 mx-auto">

<div class="flex flex-col md:flex-row gap-4">

        <div class="md:w-3/4">
    @include('shared/alerts')

            <div class="grid gap-5 lg:grid-cols-12">
                <div class="lg:col-span-12 space-y-5">
                    <div class="card">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.services.subusers.manage') }}</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.services.subusers.active_description') }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('front.services.subusers.update', ['service' => $service]) }}" class="mt-5">
                            @csrf
                            <div class="overflow-hidden rounded-xl border dark:border-gray-700">
                                <div class="flex items-center justify-between gap-4 border-b p-4 dark:border-gray-700">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white">
                                            <i class="bi bi-person-fill text-2xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ auth('web')->user()->fullName }}</p>
                                            <p class="truncate text-sm font-medium text-gray-500">{{ auth('web')->user()->email }}</p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded bg-blue-100 px-2.5 py-1 text-xs font-semibold uppercase text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
                                        {{ __('client.subusers.owner_badge') }}
                                    </span>
                                </div>
                                @forelse ($accesses as $access)
                                <div class="border-b p-4 last:border-b-0 dark:border-gray-700">
                                    <div class="grid gap-4 md:grid-cols-[3rem_1fr_auto] md:items-start">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white">
                                            <i class="bi bi-person-fill text-2xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <label class="flex items-start gap-3">
                                                <span class="min-w-0">
                                                    <span class="block truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $access->subCustomer->fullName }}</span>
                                                    <span class="block truncate text-sm font-medium text-gray-500">{{ $access->subCustomer->email }}</span>
                                                </span>
                                            </label>
                                            <div class="mt-3 space-y-1.5">
                                                @if ($access->all_services)
                                                <span class="inline-flex rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">{{ __('client.subusers.all_services_locked') }}</span>
                                                @endif
                                                @foreach ($servicePermissions as $permission)
                                                <div>
                                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <input type="checkbox" name="permissions[{{ $access->id }}][]" value="{{ $permission }}" class="rounded border-gray-300 text-blue-600" @checked(in_array($permission, $access->permissions ?? [], true))>
                                                        {{ __('permissions.subusers.' . str_replace('.', '_', $permission)) }}
                                                    </label>
                                                    @if (in_array($permission, \App\Models\Account\CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES, true))
                                                        <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">{{ __('client.subusers.invoice_permissions_auto_granted') }}</p>
                                                    @endif
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <a href="{{ route('front.subusers.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ __('client.subusers.manage_global') }}</a>
                                    </div>
                                </div>
                                @empty
                                <div class="rounded-lg border border-dashed p-8 text-center dark:border-gray-700">
                                    <i class="bi bi-people text-3xl text-gray-300 dark:text-gray-600"></i>
                                    <p class="mt-3 text-sm text-gray-500">{{ __('client.subusers.no_active_accesses') }}</p>
                                </div>
                                @endforelse
                            </div>
                            @if ($accesses->isNotEmpty())
                            <button class="btn btn-primary mt-5">{{ __('global.save') }}</button>
                            @endif
                        </form>
                        <form id="invite-service-subuser-form" method="POST" action="{{ route('front.services.subusers.store', ['service' => $service]) }}" class="lg:col-span-12 mt-2 permissions-form">
                            @csrf
                            <div class="grid gap-2 lg:grid-cols-12">
                                <div class="lg:col-span-12">
                                    
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.invite.title') }}</h2>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.subusers.invite.service_description') }}</p>
                                </div>
                                <div class="lg:col-span-12">
                                    @include('shared/input', ['name' => 'email', 'label' => __('global.email'), 'required' => true, 'type' => 'email'])


                                </div>
                                <div class="lg:col-span-12">
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        @foreach ($servicePermissions as $permission)
                                        <div>
                                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300 text-indigo-600" @checked($permission==='service.show' )>
                                                {{ __('permissions.subusers.' . str_replace('.', '_', $permission)) }}
                                            </label>
                                            @if (in_array($permission, \App\Models\Account\CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES, true))
                                                <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">{{ __('client.subusers.invoice_permissions_auto_granted') }}</p>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            <button class="btn btn-primary mt-2">{{ __('client.subusers.invite.submit') }}</button>

                            </div>

                        </form>

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.pending_invitations') }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.services.subusers.pending_description') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($invitations as $invitation)
                            <div class="flex flex-col gap-3 rounded-lg border p-4 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $invitation->email }}</p>
                                    <p class="text-sm text-gray-500">{{ __('client.subusers.expires_at') }} {{ optional($invitation->expires_at)->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('front.subusers.invitations.resend', $invitation) }}">
                                        @csrf
                                        <button class="btn btn-secondary btn-sm">{{ __('client.subusers.resend') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('front.subusers.invitations.revoke', $invitation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">{{ __('global.delete') }}</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('global.no_results') }}</p>
                        @endforelse
                    </div>
                    </div>

                </div>

            </div>
        </div>
        <div class="md:w-1/4">
            <div class="grid grid-col-1">

                <a href="{{ route('front.services.show', ['service' => $service]) }}" class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                    {{ __('client.services.managebtn') }}
                </a>
                @if ($service->canUpgrade())
                <a href="{{ route('front.services.upgrade', ['service' => $service]) }}" class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3">
                    <i class="bi bi-arrows-angle-expand"></i>
                    {{ __('client.services.upgradeservice') }}
                </a>
                @endif

                @if ($service->configoptions->isNotEmpty())
                <a class="hs-dropdown-toggle btn-action-with-icon mb-2 p-3" href="{{ route('front.services.options', ['service' => $service]) }}">
                    <i class="bi bi-boxes"></i>
                    {{ __('client.services.manageoptions') }}
                </a>
                @endif

                @if (app('extension')->extensionIsEnabled('customers_reviews'))
                @include('customers_reviews::service_button', ['service' => $service])
                @endif

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
                                <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                                </svg>

                            </div>
                        </div>
                        <div class="grow">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ $service->name }}
                                </p>
                                <x-badge-state state="{{ $service->status }}" class="mt-1"></x-badge-state>
                            </div>

                        </div>
                    </div>

                    @if (!empty($service->description))
                    <div class="px-5 pb-5">
                        <p class="text-xs block font-medium text-gray-800 dark:text-gray-600">
                            {!! nl2br($service->description) !!}
                        </p>
                    </div>
                    @endif
                </div>
                @if ($service->server_id != null)
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                    <div class="p-4 md:p-5 flex gap-x-4">
                        <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
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
                        </div>
                    </div>
                </div>
                @endif
                @if ($service->expires_at != null)

                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                    <div class="p-4 md:p-5 flex gap-x-4">
                        <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2" />
                            </svg>
                        </div>
                        <div class="grow">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ __('client.services.expire_date') }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                    <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}" date_at="{{ $service->status == 'expired' && $service->expire_at != null ? $service->expires_at : ($service->suspended_at != null ? $service->suspended_at : $service->cancelled_at) }}"></x-service-days-remaining>

                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-2">
                    <div class="p-4 md:p-5 flex gap-x-4">
                        <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-100 rounded-lg dark:bg-gray-800">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>

                        <div class="grow">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ __('store.price') }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2">
                                <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                                    {{ formatted_price($service->getBillingPrice()->displayPrice(), $service->currency) }}
                                    <span class="text-gray-500 text-sm">/{{ $service->recurring()['unit'] }}</span>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endsection
