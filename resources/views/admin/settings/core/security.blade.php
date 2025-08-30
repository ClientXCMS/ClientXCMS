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
@section('title', __('admin.settings.core.security.title'))
@section('script')
    <script>
        const updateCaptchaLabel = function() {
            const driver = document.querySelector('select[name="captcha_driver"]').value;
            const help = document.querySelector('.captcha-help');
            const recaptcha = document.getElementById('captcha-help-recaptcha');
            const hcaptcha = document.getElementById('captcha-help-hcaptcha');
            const cloudflare = document.getElementById('captcha-help-cloudflare');

            if (driver === 'recaptcha') {
                recaptcha.style.display = 'block';
                hcaptcha.style.display = 'none';
                cloudflare.style.display = 'none';
            } else if (driver === 'hcaptcha') {
                recaptcha.style.display = 'none';
                hcaptcha.style.display = 'block';
                cloudflare.style.display = 'none';
            } else if (driver === 'cloudflare') {
                recaptcha.style.display = 'none';
                hcaptcha.style.display = 'none';
                cloudflare.style.display = 'block';
            } else {
                recaptcha.style.display = 'none';
                hcaptcha.style.display = 'none';
                cloudflare.style.display = 'none';
            }
        };
        document.addEventListener('DOMContentLoaded', updateCaptchaLabel);
        document.querySelector('select[name="captcha_driver"]').addEventListener('change', updateCaptchaLabel);
    </script>
@endsection
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.security.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.security.description') }}
        </p>

        <form method="POST" enctype="multipart/form-data">
            @csrf
                @include("admin/shared/select", [
                    "label" => __("admin.settings.core.security.fields.hash_driver"),
                    "name" => "hash_driver",
                    "value" => env('HASH_DRIVER', 'bcrypt'),
                    "options" => $drivers,
                    "help" => __("admin.settings.core.security.fields.hash_driver_help")
                ])

            @include('admin/shared/input', [
                    'label' => __('admin.settings.core.security.fields.admin_prefix'),
                    'name' => 'admin_prefix',
                    'value' => admin_prefix()
            ])

            @include('admin/shared/input', [
                    'label' => __('admin.settings.core.security.fields.gdrp_cookies_privacy_link'),
                    'name' => 'gdrp_cookies_privacy_link',
                    'value' => setting('gdrp_cookies_privacy_link', 'https://clientxcms.com/privacy'),
            ])
            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mt-2">{{ __('admin.settings.core.security.captcha.title') }}</h3>

            <div class="grid grid-cols-3 gap-4">
                <div>
                @include('admin/shared/select', [
                    'label' => __('admin.settings.core.security.captcha.fields.driver'),
                    'name' => 'captcha_driver',
                    'value' => setting('captcha_driver'),
                    'options' => $captcha,
                ])
                </div>
                <div>
                @include('admin/shared/password', [
                    'label' => __('admin.settings.core.security.captcha.fields.site_key'),
                    'name' => 'captcha_site_key',
                    'value' => setting('captcha_site_key'),
                ])
                </div>
                <div>
                @include('admin/shared/password', [
                    'label' => __('admin.settings.core.security.captcha.fields.secret_key'),
                    'name' => 'captcha_secret_key',
                    'value' => setting('captcha_secret_key'),
                ])
                </div>
            </div>

            <div class="captcha-help">
                <p class="text-sm text-gray-500 mt-2" id="captcha-help-recaptcha">{!! __('admin.settings.core.security.captcha.fields.driver_recaptcha_help', ['url' => 'google.com/recaptcha']) !!}</p>
                <p class="text-sm text-gray-500 mt-2" id="captcha-help-hcaptcha">{!! __('admin.settings.core.security.captcha.fields.driver_hcaptcha_help', ['url' => 'www.hcaptcha.com/']) !!}</p>
                <p class="text-sm text-gray-500 mt-2" id="captcha-help-cloudflare">{!! __('admin.settings.core.security.captcha.fields.driver_cloudflare_help', ['url' => 'dash.cloudflare.com/?to=/:account/turnstile']) !!}</p>
            </div>
            <h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 my-2">{{ __('admin.settings.core.security.auth') }}</h3>

            @include('admin/shared/input', [
                    'label' => __('admin.settings.core.security.fields.password_timeout'),
                    'name' => 'password_timeout',
                    'value' => setting('password_timeout', config('auth.password_timeout')),
                    'help' => __('admin.settings.core.security.fields.password_timeout_help')
                    ])
            @include('admin/shared/input', [
                    'label' => __('admin.settings.core.security.fields.banned_emails'),
                    'name' => 'banned_emails',
                    'value' => setting('banned_emails'),
                    'help' => __('admin.settings.core.security.fields.banned_emails_help')
            ])
            <div class="grid grid-cols-2 gap-4">

            @include('admin/shared/checkbox', [
                'label' => __('admin.settings.core.security.fields.allow_registration'),
                'name' => 'allow_registration',
                'checked' => setting('allow_registration'),
            ])
            @include('admin/shared/checkbox', [
                'label' => __('admin.settings.core.security.fields.auto_confirm_registration'),
                'name' => 'auto_confirm_registration',
                'checked' => setting('auto_confirm_registration'),
            ])
            @include('admin/shared/checkbox', [
                'label' => __('admin.settings.core.security.fields.force_login_client'),
                'name' => 'force_login_client',
                'checked' => setting('force_login_client'),
            ])
            @include('admin/shared/checkbox', [
                'label' => __('admin.settings.core.security.fields.allow_reset_password'),
                'name' => 'allow_reset_password',
                'checked' => setting('allow_reset_password'),
            ])
            </div>
            @method('PUT')
            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
@endsection
