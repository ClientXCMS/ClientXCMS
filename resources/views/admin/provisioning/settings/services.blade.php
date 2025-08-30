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

@extends('admin.settings.sidebar')
@section('title', __('provisioning.admin.settings.services.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('provisioning.admin.settings.services.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('provisioning.admin.settings.services.description') }}
        </p>

        <form action="{{ route('admin.settings.provisioning.services') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    @include('admin/shared/input', ['label' => __('provisioning.admin.settings.services.fields.days_before_creation_invoice_renewal'), 'name' => 'days_before_creation_invoice_renewal', 'value' => setting('days_before_creation_invoice_renewal', 7), 'type' => 'number', 'min' => 0, 'max' => 365, 'step' => 1])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('provisioning.admin.settings.services.fields.days_before_expiration'), 'name' => 'days_before_expiration', 'value' => setting('days_before_expiration', 7), 'type' => 'number', 'min' => 0, 'max' => 365, 'step' => 1, 'help' => __('provisioning.admin.settings.services.fields.days_before_expiration_help')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('provisioning.admin.settings.services.fields.notifications_expiration_days'), 'name' => 'notifications_expiration_days', 'value' => setting('notifications_expiration_days'), 'help' => __('global.separebycomma')])
                </div>
            </div>
            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('provisioning.admin.settings.services.subscription') }}</h3>
            <div class="grid md:grid-cols-1 gap-4">
                <div>
                    @include('admin/shared/input', ['label' => __('provisioning.admin.settings.services.fields.max_subscription_tries'), 'name' => 'max_subscription_tries', 'value' => setting('max_subscription_tries'), 'help' => __('provisioning.admin.settings.services.fields.max_subscription_tries_help'),  'type' => 'number', 'min' => 0, 'max' => 365, 'step' => 1])
                </div>
                <div class="col-span-3">
                    <div class="flex justify-between">
                    <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('provisioning.admin.settings.services.webhookonrenew') }}</h3>
                    <div class="hs-tooltip [--trigger:click]">
                        <div class="hs-tooltip-toggle block text-center">
                            <button type="button" class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400">
                                {{ __('global.preview') }}
                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m18 15-6-6-6 6"></path>
                                </svg>
                            </button>

                            <div class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible hidden opacity-0 transition-opacity absolute invisible z-10 max-w-xs w-full bg-white border border-gray-100 text-start rounded-xl shadow-md dark:bg-neutral-800 dark:border-neutral-700" role="tooltip">
                                <div class="p-4">
                                    <div class="mb-3 flex justify-between items-center gap-x-3">
                                    <img src="https://cdn.clientxcms.com/ressources/docs/service.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    @include('admin/shared/password', ['label' => __('webhook.url'), 'name' => 'webhook_renewal_url', 'value' => setting('webhook_renewal_url')])
                </div>
                @method('PUT')
            </div>
            <button type="submit" class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
@endsection
