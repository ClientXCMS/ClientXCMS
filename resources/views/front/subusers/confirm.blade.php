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

@extends('layouts/auth')
@section('title', __('client.subusers.confirm.title'))
@section('content')
<div class="p-4 sm:p-7">
    <header class="text-center">
        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">
            {{ __('client.subusers.confirm.title') }}
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('client.subusers.confirm.subtitle', ['owner' => $invitation->owner->email]) }}
        </p>
    </header>

    @include('shared.alerts')

    <section class="mt-6 space-y-4" aria-labelledby="confirm-details-heading">
        <h2 id="confirm-details-heading" class="sr-only">{{ __('client.subusers.confirm.details') }}</h2>

        <dl class="space-y-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('client.subusers.confirm.owner') }}
                </dt>
                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $invitation->owner->email }}
                </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('client.subusers.confirm.scope') }}
                </dt>
                <dd class="text-right text-sm text-gray-900 dark:text-gray-100">
                    @if ($invitation->all_services)
                        {{ __('client.subusers.confirm.scope_all_services') }}
                    @else
                        <ul class="space-y-0.5">
                            @forelse ($invitation->services as $service)
                                <li>{{ $service->name }}</li>
                            @empty
                                <li class="italic text-gray-500">{{ __('client.subusers.confirm.scope_none') }}</li>
                            @endforelse
                        </ul>
                    @endif
                </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('client.subusers.confirm.permissions') }}
                </dt>
                <dd class="text-right">
                    <ul class="flex flex-wrap justify-end gap-1.5">
                        @foreach ($invitation->permissions as $permission)
                            <li>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ __('client.subusers.permissions.'.$permission) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('client.subusers.confirm.expires_at') }}
                </dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $invitation->expires_at?->translatedFormat('d F Y') }}
                </dd>
            </div>
        </dl>

        <p class="text-xs text-gray-500 dark:text-gray-500">
            {{ __('client.subusers.confirm.warning') }}
        </p>
    </section>

    <form method="POST" action="{{ route('front.subusers.accept.confirm', $token) }}" class="mt-6">
        @csrf
        <button type="submit" class="btn-primary block w-full min-h-[44px]">
            {{ __('client.subusers.confirm.accept_button') }}
        </button>
    </form>

    <form method="POST" action="{{ route('front.subusers.invitations.revoke', $invitation->id) }}"
          class="mt-3"
          onsubmit="return confirm('{{ __('client.subusers.confirm.decline_confirm') }}');">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="block w-full min-h-[44px] rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:outline-none dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            {{ __('client.subusers.confirm.decline_button') }}
        </button>
    </form>
</div>
@endsection
