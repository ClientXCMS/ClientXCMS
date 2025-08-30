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
@section('title', __('admin.settings.core.mail.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/testmail.js') }}" type="module" defer></script>
@endsection
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.mail.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.mail.description') }}
        </p>

        <form action="{{ route('admin.settings.core.email') }}" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            @csrf
                <div>
            @include('admin/shared/input', [
                'label' => __('admin.settings.core.mail.from_address'),
                'name' => 'mail_from_address',
                'value' => setting('mail_fromaddress'),
            ])
                </div>
                <div>
                @include('admin/shared/input', [
                    'label' => __('admin.settings.core.mail.from_name'),
                    'name' => 'mail_from_name',
                    'value' => setting('mail_fromname'),
                ])
                </div>


                <div>
                    @include('admin/shared/input', [
                        'label' => __('admin.settings.core.mail.greeting'),
                        'name' => 'mail_greeting',
                        'value' => setting('mail_greeting'),
                    ])
                </div>
                <div>
                @include('admin/shared/input', [
                    'label' => __('admin.settings.core.mail.salutation'),
                    'name' => 'mail_salutation',
                    'value' => setting('mail_salutation'),
                ])
                </div>
                <div>

                @include('admin/shared/input', [
                    'label' => __('admin.settings.core.mail.domain'),
                    'name' => 'mail_domain',
                    'value' => setting('mail_domain'),
                ])
                </div>

            @method('PUT')
            </div>

            <div class="relative flex items-start mr-3 mt-3">
                <div class="flex items-center h-5 mt-1">
                    <input id="hs-log-delete" name="mail_disable_mail" {{ env('MAIL_MAILER') == 'log' ? 'checked' : '' }} type="checkbox" class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"  data-hs-collapse="#hs-smtp" aria-describedby="hs-smtp-description">
                </div>
                <label for="hs-log-delete" class="ms-3">
                    <span class="block text-sm font-semibold text-gray-800 dark:text-gray-300">{{ __('admin.settings.core.mail.disable_mail') }}</span>
                    <span id="hs-log-description" class="block text-sm text-gray-600 dark:text-gray-500">{{ __('admin.settings.core.mail.disable_mail_help') }}</span>
                </label>
            </div>
            <div class="relative flex items-start mr-3 mt-3">
                <div class="flex items-center h-5 mt-1">
                    <input id="hs-checkbox-delete" name="mail_smtp_enable" {{ setting('mail_smtp_enable') ? 'checked' : '' }} type="checkbox" class="hs-collapse-toggle border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"  data-hs-collapse="#hs-smtp" aria-describedby="hs-smtp-description">
                </div>
                <label for="hs-checkbox-delete" class="ms-3">
                    <span class="block text-sm font-semibold text-gray-800 dark:text-gray-300">{{ __('admin.settings.core.mail.smtp.enabled') }}</span>
                    <span id="hs-smtp-description" class="block text-sm text-gray-600 dark:text-gray-500">{{ __('admin.settings.core.mail.smtp.description') }}</span>
                </label>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="col-span-2">
                    @include('admin/shared/input', [
                        'label' => __('admin.settings.core.mail.smtp.host'),
                        'name' => 'mail_smtp_host',
                        'value' => setting('mail_smtp_host'),
                    ])
                </div>
                <div>
                    @include('admin/shared/input', [
                        'label' => __('admin.settings.core.mail.smtp.port'),
                        'name' => 'mail_smtp_port',
                        'value' => setting('mail_smtp_port'),
                    ])
                </div>
                <div>
                    @include('admin/shared/input', [
                        'label' => __('admin.settings.core.mail.smtp.username'),
                        'name' => 'mail_smtp_username',
                        'value' => setting('mail_smtp_username'),
                    ])
                </div>
                <div>
                    @include('admin/shared/password', [
                        'label' => __('admin.settings.core.mail.smtp.password'),
                        'name' => 'mail_smtp_password',
                        'value' => setting('mail_smtp_password'),

                    ])
                </div>
                <div>
                    @include('admin/shared/select', [
                        'label' => __('admin.settings.core.mail.smtp.encryption'),
                        'name' => 'mail_smtp_encryption',
                        'value' => setting('mail_smtp_encryption'),
                        'options' => [
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                            'none' => 'None',
                        ],
                    ])
                </div>
            </div>
            <p class="text-gray-500 mt-2">{{ __('admin.settings.core.mail.test.help') }}</p>
            <button class="btn btn-primary mt-2">{{ __('global.save') }}</button>
            <button class="btn btn-secondary mt-2" type="button" id="test-connection" data-url="{{ route('admin.settings.testmail') }}">{{ __('admin.settings.core.mail.test.btn') }}</button>
            <h4 class="text-green-800 hidden" id="successTest">{{ __('admin.settings.core.mail.test.success', ['email' => auth('admin')->user()->email]) }}</h4>
            <h4 class="text-red-500 hidden" id="failedTest"></h4>
        </form>
@endsection
