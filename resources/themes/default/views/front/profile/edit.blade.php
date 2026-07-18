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
@section('title', __('client.profile.index'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/filter.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Check if we have errors in specific inputs and decide default tab
            let defaultTabId = null;
            @if (old('_subuser_form') && $errors->any())
                defaultTabId = '#pane-subusers';
            @elseif (
                $errors->hasAny([
                    'firstname',
                    'lastname',
                    'company_name',
                    'address',
                    'address2',
                    'zipcode',
                    'phone',
                    'country',
                    'city',
                    'region',
                    'locale',
                    'billing_details',
                ]))
                defaultTabId = '#pane-profile';
            @elseif (
                $errors->hasAny([
                    'currentpassword',
                    'password',
                    'password_confirmation',
                    '2fa',
                    'security_question_id',
                    'security_answer',
                    'currentpassword_sq',
                ]))
                defaultTabId = '#pane-security';
            @elseif ($errors->hasAny(['confirm_deletion']))
                defaultTabId = '#pane-danger';
            @endif

            // 2. Check URL hash or query param or fallback to defaultTabId
            const hash = window.location.hash;
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');

            let targetTabPane = null;
            if (hash && document.querySelector(`[data-hs-tab="${hash}"]`)) {
                targetTabPane = hash;
            } else if (tabParam && document.querySelector(`[data-hs-tab="#pane-${tabParam}"]`)) {
                targetTabPane = `#pane-${tabParam}`;
            } else if (defaultTabId) {
                targetTabPane = defaultTabId;
            }

            if (targetTabPane) {
                const tabBtn = document.querySelector(`[data-hs-tab="${targetTabPane}"]`);
                if (tabBtn) {
                    tabBtn.click();
                }
            }

            // 3. Listen to clicks on tab buttons to update URL hash
            document.querySelectorAll('[data-hs-tab]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const target = btn.getAttribute('data-hs-tab');
                    if (target) {
                        history.replaceState(null, null, target);
                    }
                });
            });

            // 4. Auto-submit 2FA options checkbox on change
            const emailNewIpCheckbox = document.querySelector('input[name="2fa_email_new_ip"]');
            if (emailNewIpCheckbox) {
                emailNewIpCheckbox.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            }
        });
    </script>
@endsection
@section('content')
    <div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @include('shared/alerts')

        <div class="card p-0 overflow-hidden" style="padding: 0 !important;">
            <div class="flex flex-col md:flex-row min-h-[500px]">
                
                <div class="w-full md:w-1/4 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 p-4">
                    <nav class="flex flex-col space-y-1.5" aria-label="Tabs" role="tablist" aria-orientation="vertical">
                        <button type="button"
                            class="hs-tab-active:text-primary dark:hs-tab-active:bg-indigo-950/40 dark:hs-tab-active:text-indigo-300 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 active text-left w-full"
                            id="tab-profile-item" data-hs-tab="#pane-profile" aria-controls="pane-profile" role="tab">
                            <i class="bi bi-person text-lg"></i>
                            {{ __('client.profile.index') }}
                        </button>
                        <button type="button"
                            class="hs-tab-active:text-primary dark:hs-tab-active:bg-indigo-950/40 dark:hs-tab-active:text-indigo-300 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 text-left w-full"
                            id="tab-security-item" data-hs-tab="#pane-security" aria-controls="pane-security" role="tab">
                            <i class="bi bi-shield-lock text-lg"></i>
                            {{ __('client.profile.security.index') }}
                        </button>
                        <button type="button"
                            class="hs-tab-active:text-primary dark:hs-tab-active:bg-indigo-950/40 dark:hs-tab-active:text-indigo-300 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 text-left w-full"
                            id="tab-subusers-item" data-hs-tab="#pane-subusers" aria-controls="pane-subusers" role="tab">
                            <i class="bi bi-people text-lg"></i>
                            {{ __('client.subusers.account_access') }}
                        </button>
                        <div class="ms-5 border-s border-gray-200 ps-3 dark:border-gray-700" aria-label="{{ __('client.subusers.account_access') }}">
                            <button type="button" data-subuser-section-target="accesses" class="subuser-section-button flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-xs font-medium text-gray-500 hover:bg-white hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700">
                                <span>{{ __('client.subusers.active_accesses') }}</span>
                                <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] text-gray-700 dark:bg-gray-600 dark:text-gray-200">{{ $ownedAccountAccesses->count() }}</span>
                            </button>
                            <button type="button" data-subuser-section-target="invite" class="subuser-section-button flex w-full items-center rounded-md px-3 py-2 text-left text-xs font-medium text-gray-500 hover:bg-white hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700">
                                {{ __('client.subusers.invite.title') }}
                            </button>
                            <button type="button" data-subuser-section-target="invitations" class="subuser-section-button flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-xs font-medium text-gray-500 hover:bg-white hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700">
                                <span>{{ __('client.subusers.pending_invitations') }}</span>
                                <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] text-gray-700 dark:bg-gray-600 dark:text-gray-200">{{ $accountInvitations->count() }}</span>
                            </button>
                            <button type="button" data-subuser-section-target="received" class="subuser-section-button flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-xs font-medium text-gray-500 hover:bg-white hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700">
                                <span>{{ __('client.subusers.received_accesses') }}</span>
                                <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] text-gray-700 dark:bg-gray-600 dark:text-gray-200">{{ $receivedAccountAccesses->count() }}</span>
                            </button>
                        </div>
                        @if (isset($providers) && count($providers) > 0)
                            <button type="button"
                                class="hs-tab-active:text-primary dark:hs-tab-active:bg-indigo-950/40 dark:hs-tab-active:text-indigo-300 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 text-left w-full"
                                id="tab-social-item" data-hs-tab="#pane-social" aria-controls="pane-social" role="tab">
                                <i class="bi bi-link-45deg text-lg"></i>
                                {{ __('client.profile.connected_accounts') }}
                            </button>
                        @endif
                        <button type="button"
                            class="hs-tab-active:text-primary dark:hs-tab-active:bg-indigo-950/40 dark:hs-tab-active:text-indigo-300 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 text-left w-full"
                            id="tab-export-item" data-hs-tab="#pane-export" aria-controls="pane-export" role="tab">
                            <i class="bi bi-download text-lg"></i>
                            {{ __('client.gdpr.export.title') }}
                        </button>
                        <button type="button"
                            class="hs-tab-active:bg-red-50 hs-tab-active:text-red-600 dark:hs-tab-active:bg-red-950/40 dark:hs-tab-active:text-red-400 py-3 px-4 inline-flex items-center gap-x-3 rounded-lg text-sm font-medium text-gray-500 hover:text-red-600 focus:outline-none focus:text-red-600 text-left w-full"
                            id="tab-danger-item" data-hs-tab="#pane-danger" aria-controls="pane-danger" role="tab">
                            <i class="bi bi-exclamation-triangle text-lg"></i>
                            {{ __('client.profile.delete.danger_zone') }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Panels Body (3/4 width on desktop) -->
                <div class="w-full md:w-3/4 p-6">

                    <!-- Profile Panel -->
                    <div id="pane-profile" role="tabpanel" aria-labelledby="tab-profile-item">
                        <div class="card-heading border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('client.profile.index') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('client.profile.index_description') }}
                                </p>
                            </div>
                            <x-avatar-editor
                                :user="auth('web')->user()"
                                :upload-route="route('front.profile.avatar.upload')"
                                :delete-route="route('front.profile.avatar.delete')"
                                input-id="customer-avatar"
                                variant="field"
                                class="w-full md:w-auto md:max-w-sm"
                            />
                        </div>
                        <form method="POST" action="{{ route('front.profile.update') }}">
                            @csrf
                            <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">

                                <div class="sm:col-span-2">
                                    @include('shared.input', [
                                        'name' => 'firstname',
                                        'label' => __('global.firstname'),
                                        'value' => auth('web')->user()->firstname ?? old('firstname'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared.input', [
                                        'name' => 'lastname',
                                        'label' => __('global.lastname'),
                                        'value' => auth('web')->user()->lastname ?? old('lastname'),
                                    ])
                                </div>

                                <div class="sm:col-span-2">
                                    @include('shared/input', [
                                        'name' => 'company_name',
                                        'label' => __('global.company_name') . ' (' . __('global.optional') . ')',
                                        'value' => auth('web')->user()->company_name ?? old('company_name'),
                                    ])
                                </div>
                                <div class="sm:col-span-3">
                                    @include('shared.input', [
                                        'name' => 'address',
                                        'label' => __('global.address'),
                                        'value' => auth('web')->user()->address ?? old('address'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared.input', [
                                        'name' => 'address2',
                                        'label' => __('global.address2'),
                                        'value' => auth('web')->user()->address2 ?? old('address2'),
                                    ])
                                </div>
                                <div class="sm:col-span-1">
                                    @include('shared.input', [
                                        'name' => 'zipcode',
                                        'label' => __('global.zip'),
                                        'value' => auth('web')->user()->zipcode ?? old('zipcode'),
                                    ])
                                </div>
                                <div class="sm:col-span-3">
                                    @include('shared.input', [
                                        'name' => 'email',
                                        'label' => __('global.email'),
                                        'type' => 'email',
                                        'value' => auth('web')->user()->email ?? old('email'),
                                        'disabled' => true,
                                    ])
                                </div>
                                <div class="sm:col-span-3">
                                    @include('shared.phone-intl', [
                                        'name' => 'phone',
                                        'label' => __('global.phone'),
                                        'value' => auth('web')->user()->phone ?? old('phone'),
                                        'country' => auth('web')->user()->country ?? old('country', 'FR'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared.select', [
                                        'name' => 'country',
                                        'label' => __('global.country'),
                                        'options' => $countries,
                                        'value' => auth('web')->user()->country ?? old('country'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared.input', [
                                        'name' => 'city',
                                        'label' => __('global.city'),
                                        'value' => auth('web')->user()->city ?? old('city'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared.input', [
                                        'name' => 'region',
                                        'label' => __('global.region'),
                                        'value' => auth('web')->user()->region ?? old('region'),
                                    ])
                                </div>
                                <div class="sm:col-span-2">
                                    @include('shared/select', [
                                        'name' => 'locale',
                                        'label' => __('global.locale'),
                                        'options' => $locales,
                                        'value' => auth('web')->user()->locale ?? old('locale'),
                                    ])
                                </div>
                                <div class="sm:col-span-4">
                                    @include('shared/textarea', [
                                        'name' => 'billing_details',
                                        'label' => __('global.billing_details'),
                                        'value' => auth('web')->user()->billing_details ?? old('billing_details'),
                                        'help' => __('global.billing_details_help'),
                                    ])
                                </div>
                            </div>
                            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
                        </form>
                    </div>

                    <!-- Security Panel -->
                    <div id="pane-security" class="hidden" role="tabpanel" aria-labelledby="tab-security-item">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                            <!-- Left Column: Password & Security Question -->
                            <div class="space-y-6">
                                <div>
                                    <div class="card-heading border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                                        <div>
                                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __('client.profile.security.title') }}
                                            </h2>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('client.profile.security.subheading') }}
                                            </p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('front.profile.password') }}">
                                        @csrf
                                        <div class="space-y-4">
                                            <div>
                                                @include('shared/password', [
                                                    'name' => 'currentpassword',
                                                    'label' => __('client.profile.security.currentpassword'),
                                                ])
                                            </div>
                                            <div>
                                                @include('shared/password', [
                                                    'name' => 'password',
                                                    'label' => __('client.profile.security.newpassword'),
                                                ])
                                            </div>
                                            <div>
                                                @include('shared/password', [
                                                    'name' => 'password_confirmation',
                                                    'label' => __('global.password_confirmation'),
                                                ])
                                            </div>

                                            @if (auth('web')->user()->twoFactorEnabled())
                                                <div>
                                                    @include('shared/input', [
                                                        'name' => '2fa',
                                                        'label' => __('client.profile.2fa.code'),
                                                    ])
                                                </div>
                                            @endif
                                            @if ($user->hasSecurityQuestion())
                                                <div>
                                                    @include('shared/input', [
                                                        'name' => 'security_answer',
                                                        'label' => $user->securityQuestion->question,
                                                    ])
                                                </div>
                                            @endif
                                        </div>
                                        <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
                                    </form>
                                </div>

                                @if (!$user->hasSecurityQuestion())
                                    <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <div class="mb-4">
                                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __('client.profile.security_question.title') }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('client.profile.security_question.description') }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('front.profile.security_question') }}">
                                            @csrf
                                            <div class="grid grid-cols-1 gap-4">
                                                <div>
                                                    @include('shared/select', [
                                                        'name' => 'security_question_id',
                                                        'label' => __('client.profile.security_question.select'),
                                                        'options' => \App\Models\Admin\SecurityQuestion::active()->ordered()->pluck('question', 'id')->toArray(),
                                                        'value' => old('security_question_id', $user->security_question_id),
                                                        'required' => true,
                                                        'placeholder' => __('client.profile.security_question.choose'),
                                                    ])
                                                </div>
                                                <div>
                                                    @include('shared/input', [
                                                        'name' => 'security_answer',
                                                        'label' => __('client.profile.security_question.answer'),
                                                        'type' => 'text',
                                                        'placeholder' => __(
                                                            'client.profile.security_question.answer_placeholder'),
                                                        'required' => true,
                                                    ])
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                @include('shared/password', [
                                                    'name' => 'currentpassword_sq',
                                                    'label' => __('client.profile.security.currentpassword'),
                                                ])
                                            </div>
                                            <button type="submit" class="btn btn-primary mt-4">
                                                {{ __('global.save') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <!-- Right Column: 2FA & Trusted Devices -->
                            <div class="space-y-6">
                                <div>
                                    <div class="card-heading border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                                        <div>
                                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __('client.profile.2fa.title') }}
                                            </h2>
                                            @if (!auth('web')->user()->twoFactorEnabled())
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ __('client.profile.2fa.info') }}
                                                </p>
                                            @else
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {!! __('client.profile.2fa.download_codes', ['url' => route('front.profile.2fa_codes')]) !!}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('front.profile.2fa') }}">
                                        @csrf
                                        @if (!auth('web')->user()->twoFactorEnabled())
                                            <div class="flex justify-center mb-4">
                                                <div class="p-2 bg-white rounded-lg border border-gray-200 dark:bg-slate-900 dark:border-gray-700">
                                                    {!! $qrcode !!}
                                                </div>
                                            </div>
                                            @include('shared/input', [
                                                'name' => '2fa',
                                                'label' => __('client.profile.2fa.code'),
                                                'help' => $code,
                                            ])
                                        @else
                                            @include('shared/input', [
                                                'name' => '2fa',
                                                'label' => __('client.profile.2fa.code'),
                                            ])
                                        @endif
                                        <button
                                            class="btn {{ auth('web')->user()->twoFactorEnabled() ? 'bg-red-600 text-white hover:bg-red-700' : 'btn-primary' }} mt-4">
                                            {{ __(auth('web')->user()->twoFactorEnabled() ? 'global.delete' : 'global.save') }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('front.profile.2fa_options') }}"
                                        class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        @csrf
                                        @include('shared/checkbox', [
                                            'name' => '2fa_email_new_ip',
                                            'label' => __('client.profile.2fa.email_new_ip'),
                                            'checked' => auth('web')->user()->twoFactorEmailOnNewIpEnabled(),
                                        ])
                                    </form>

                                    @php
                                        $trustedDevices = auth('web')->user()->twoFactorTrustedIps();
                                        $currentIp = request()->ip();
                                    @endphp
                                    <section class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700"
                                        aria-labelledby="trusted-devices-heading">
                                        <header class="flex items-baseline justify-between gap-3">
                                            <h3 id="trusted-devices-heading"
                                                class="text-base font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __('client.profile.2fa.trusted_devices_heading') }}
                                            </h3>
                                            @if (count($trustedDevices) > 0)
                                                <form method="POST"
                                                    action="{{ route('front.profile.2fa_trusted_revoke_all') }}"
                                                    onsubmit="return confirm('{{ __('client.profile.2fa.trusted_devices_revoke_all') }} ?');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-sm text-red-600 underline-offset-2 hover:underline focus-visible:underline focus-visible:outline-none dark:text-red-400">
                                                        {{ __('client.profile.2fa.trusted_devices_revoke_all') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </header>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ __('client.profile.2fa.trusted_devices_description') }}
                                        </p>

                                        @if (count($trustedDevices) === 0)
                                            <p class="mt-4 text-sm italic text-gray-500 dark:text-gray-500">
                                                {{ __('client.profile.2fa.trusted_devices_empty') }}
                                            </p>
                                        @else
                                            <ul class="mt-4 space-y-2" role="list">
                                                @foreach ($trustedDevices as $device)
                                                    <li
                                                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                                        <div class="min-w-0 flex-1">
                                                            <p
                                                                class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                <span>{{ \App\Support\UserAgentLabel::summarize($device['user_agent']) }}</span>
                                                                @if ($device['ip'] === $currentIp)
                                                                    <span
                                                                        class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                                        <i class="bi bi-check-circle-fill mr-1"
                                                                            aria-hidden="true"></i>
                                                                        {{ __('client.profile.2fa.current_device') }}
                                                                    </span>
                                                                @endif
                                                            </p>
                                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                                <span class="font-mono">{{ $device['ip'] }}</span>
                                                                &middot;
                                                                @if ($device['until'])
                                                                    {{ __('client.profile.2fa.trusted_device_expires_at', ['date' => $device['until']]) }}
                                                                @else
                                                                    {{ __('client.profile.2fa.trusted_device_expires_never') }}
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <form method="POST"
                                                            action="{{ route('front.profile.2fa_trusted_revoke') }}"
                                                            class="flex-shrink-0">
                                                            @csrf
                                                            <input type="hidden" name="ip"
                                                                value="{{ $device['ip'] }}">
                                                            <button type="submit"
                                                                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:outline-none dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20"
                                                                aria-label="{{ __('client.profile.2fa.trusted_device_revoke') }} {{ $device['ip'] }}">
                                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                                <span>{{ __('client.profile.2fa.trusted_device_revoke') }}</span>
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subusers Panel -->
                    <div id="pane-subusers" class="hidden" role="tabpanel" aria-labelledby="tab-subusers-item">
                        @include('front.subusers.manager')
                    </div>

                    <!-- Social Accounts Panel -->
                    @if (isset($providers) && count($providers) > 0)
                        <div id="pane-social" class="hidden" role="tabpanel" aria-labelledby="tab-social-item">
                            <div class="card-heading border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.profile.connected_accounts') }}
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('socialauth::messages.description') ?? 'Gérez la synchronisation de vos comptes avec les réseaux sociaux.' }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($providers ?? [] as $provider)
                                    @if ($provider->isSynced())
                                        <a href="{{ route('socialauth.unlink', $provider->name) }}"
                                            class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                                            <img src="{{ $provider->provider()->logo() }}"
                                                alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                                            {{ __('socialauth::messages.unlink', ['provider' => $provider->provider()->title()]) }}
                                        </a>
                                    @else
                                        <a href="{{ route('socialauth.authorize', $provider->name) }}"
                                            class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                                            <img src="{{ $provider->provider()->logo() }}"
                                                alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                                            {{ __('socialauth::messages.sync_with', ['provider' => $provider->provider()->title()]) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Data Export Panel -->
                    <div id="pane-export" class="hidden" role="tabpanel" aria-labelledby="tab-export-item">
                        <div class="card-heading border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('client.gdpr.export.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('client.gdpr.export.description') }}
                                </p>
                            </div>
                        </div>

                        @if (session('gdpr_export_url'))
                            <div
                                class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex-shrink-0 bg-green-100 dark:bg-green-900/40 p-2 rounded-full text-green-600 dark:text-green-400">
                                            <i class="bi bi-check-circle-fill text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-green-800 dark:text-green-200 font-semibold">
                                                {{ __('client.gdpr.export.ready') }}</p>
                                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                                {{ __('client.gdpr.export.info') }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ session('gdpr_export_url') }}" class="btn btn-success whitespace-nowrap">
                                        <i class="bi bi-download mr-1"></i>
                                        {{ __('client.gdpr.export.download') }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div
                            class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex-shrink-0 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 p-3 rounded-lg">
                                    <i class="bi bi-file-earmark-zip text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.gdpr.export.btn') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ __('client.gdpr.export.description') }}
                                    </p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('front.profile.export') }}" class="w-full md:w-auto">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full md:w-auto">
                                    <i class="bi bi-arrow-right-circle mr-1"></i>
                                    {{ __('client.gdpr.export.btn') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Danger Zone Panel -->
                    <div id="pane-danger" class="hidden" role="tabpanel" aria-labelledby="tab-danger-item">
                        @php
                            $deletionService = new \App\Services\Account\AccountDeletionService();
                            $canDelete = $deletionService->canDelete($user);
                            $blockingReasons = $deletionService->getBlockingReasons($user);
                        @endphp

                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-red-700 dark:text-red-400">
                                        {{ __('client.profile.delete.danger_zone') }}</h3>
                                    <p class="mt-2 text-red-600 dark:text-red-300">
                                        {{ __('client.profile.delete.warning_message') }}</p>

                                    @if (!$canDelete)
                                        <div
                                            class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                            <p class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                                {{ __('client.profile.delete.cannot_delete') }}</p>
                                            @if (isset($blockingReasons['active_services']))
                                                <p class="text-yellow-700 dark:text-yellow-300">
                                                    {{ __('client.profile.delete.has_active_services', ['count' => $blockingReasons['active_services']['count']]) }}
                                                </p>
                                            @endif
                                            @if (isset($blockingReasons['pending_invoices']))
                                                <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                                                    {{ __('client.profile.delete.has_pending_invoices', ['count' => $blockingReasons['pending_invoices']['count']]) }}
                                                </p>
                                            @endif
                                        </div>
                                    @else
                                        <form action="{{ route('front.profile.delete.confirm') }}" method="POST"
                                            class="mt-4"
                                            onsubmit="return confirm('{{ __('client.profile.delete.final_confirm') }}')">
                                            @csrf
                                            @method('DELETE')

                                            <div class="space-y-4">
                                                <div>
                                                    @include('shared/input', [
                                                        'name' => 'password',
                                                        'type' => 'password',
                                                        'label' => __('client.profile.delete.password_label'),
                                                    ])
                                                </div>

                                                @if ($user->twoFactorEnabled())
                                                    <div>
                                                        @include('shared/input', [
                                                            'name' => '2fa_code',
                                                            'type' => 'text',
                                                            'label' => __('client.profile.delete.2fa_label'),
                                                        ])
                                                    </div>
                                                @endif

                                                <div>
                                                    @include('shared/checkbox', [
                                                        'name' => 'confirm_deletion',
                                                        'label' => __('client.profile.delete.confirm_checkbox'),
                                                        'checked' => false,
                                                    ])
                                                </div>
                                            </div>

                                            <button type="submit" class="mt-4 w-full btn btn-danger">
                                                {{ __('client.profile.delete.submit_button') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
