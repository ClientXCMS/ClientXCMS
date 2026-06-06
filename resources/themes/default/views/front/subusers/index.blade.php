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
@section('title', __('client.subusers.index'))
@section('content')
    <div class="max-w-[85rem] py-5 lg:py-7 mx-auto">
        @include('shared/alerts')

        <div class="grid gap-5 lg:grid-cols-12">
            <div class="card lg:col-span-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.active_accesses') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.subusers.account_access_description') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $accesses->count() }}</span>
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border dark:border-gray-700">
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
                            <form method="POST" action="{{ route('front.subusers.accesses.update', $access) }}">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-4 md:grid-cols-[3rem_1fr_auto] md:items-start">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white">
                                        <i class="bi bi-person-fill text-2xl"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <p class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $access->subCustomer->fullName }}</p>
                                            @if ($access->subCustomer->twoFactorEnabled())
                                                <i class="bi bi-shield-fill-check text-amber-500"></i>
                                            @endif
                                        </div>
                                        <p class="truncate text-sm font-medium text-gray-500">{{ $access->subCustomer->email }}</p>
                                        @include('front.subusers.permissions-form', ['model' => $access, 'permissions' => $permissions, 'services' => $services])
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <button class="btn btn-primary btn-sm">{{ __('global.update') }}</button>
                                            <button form="delete-access-{{ $access->id }}" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('client.subusers.confirm_revoke') }}')">{{ __('global.delete') }}</button>
                                        </div>
                                    </div>
                                    <span class="h-fit shrink-0 rounded bg-gray-100 px-2.5 py-1 text-xs font-semibold uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ __('client.subusers.subuser_badge') }}
                                    </span>
                                </div>
                            </form>
                            <form id="delete-access-{{ $access->id }}" method="POST" action="{{ route('front.subusers.accesses.destroy', $access) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed p-8 text-center dark:border-gray-700">
                            <i class="bi bi-people text-3xl text-gray-300 dark:text-gray-600"></i>
                            <p class="mt-3 text-sm text-gray-500">{{ __('client.subusers.no_active_accesses') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <form id="invite-subuser-form" method="POST" action="{{ route('front.subusers.store') }}" class="card permissions-form lg:col-span-4">
                @csrf
                <div class="grid gap-2 lg:grid-cols-12">
                    <div class="lg:col-span-12">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.invite.title') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.subusers.invite.description') }}</p>
                    </div>
                    <div class="lg:col-span-12">
                        @include('shared/input', [
                            'name' => 'email',
                            'label' => __('global.email'),
                            'type' => 'email',
                            'required' => true,
                        ])
                    </div>
                    <div class="lg:col-span-12">
                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="all_services" value="1" class="rounded border-gray-300 text-blue-600">
                            {{ __('client.subusers.all_services') }}
                        </label>
                        <div class="mt-3 space-y-1.5">
                            @foreach ($services as $service)
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" class="rounded border-gray-300 text-blue-600">
                                    {{ $service->excerptName() }} - {{ $service->expires_at?->format('d/m/Y') ?? __('global.onetime') }} - <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}"></x-service-days-remaining>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="lg:col-span-12">
                        <div class="mt-3 grid gap-5">
                            @foreach ($permissions as $group => $items)
                                <div class="{{ $group === 'services' ? 'lg:col-span-8' : 'lg:col-span-4' }}">
                                    <p class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('permissions.subusers.groups.' . $group) }}</p>
                                    <div class="{{ $group === 'services' ? 'grid gap-2 sm:grid-cols-2' : 'space-y-1.5' }}">
                                        @foreach ($items as $permission)
                                            <div>
                                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300 text-blue-600">
                                                    {{ __('permissions.subusers.' . str_replace('.', '_', $permission)) }}
                                                </label>
                                                @if (in_array($permission, \App\Models\Account\CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES, true))
                                                    <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">{{ __('client.subusers.invoice_permissions_auto_granted') }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                    <button class="btn btn-primary mt-2">{{ __('client.subusers.invite.submit') }}</button>

            </form>

            <div class="card lg:col-span-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.pending_invitations') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.services.subusers.pending_description') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $invitations->count() }}</span>
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

            <div class="card lg:col-span-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('client.subusers.received_accesses') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.subusers.received_accesses_description') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $receivedAccesses->count() }}</span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($receivedAccesses as $access)
                        <div class="flex flex-col gap-3 rounded-lg border p-4 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $access->owner->fullName }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $access->all_services ? __('client.subusers.all_services') : trans_choice('client.subusers.services_count', $access->services->count(), ['count' => $access->services->count()]) }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('front.subusers.accesses.destroy', $access) }}" onsubmit="return confirm('{{ __('client.subusers.confirm_leave') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">{{ __('client.subusers.leave_access') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('global.no_results') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
