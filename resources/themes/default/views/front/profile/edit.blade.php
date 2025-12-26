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
@endsection
@section('content')
<div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
    @include('shared/alerts')

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <div class="grid-cols-1 sm:col-span-2">
            <div class="card">
                <div class="card-heading">

                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __('client.profile.index') }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('client.profile.index_description') }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('front.profile.update') }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">

                        <div class="sm:col-span-2">
                            @include("shared.input", ["name" => "firstname", "label" => __('global.firstname'), "value" => auth('web')->user()->firstname ?? old("firstname")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared.input", ["name" => "lastname", "label" => __('global.lastname'), "value" => auth('web')->user()->lastname ?? old("lastname")])
                        </div>

                        <div class="sm:col-span-2">
                            @include("shared/input", ["name" => "company_name", "label" => __('global.company_name') . ' (' . __('global.optional') . ')', "value" => auth('web')->user()->company_name ?? old("company_name")])
                        </div>
                        <div class="sm:col-span-3">
                            @include("shared.input", ["name" => "address", "label" => __('global.address'), "value" => auth('web')->user()->address ?? old("address")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared.input", ["name" => "address2", "label" => __('global.address2'), "value" => auth('web')->user()->address2 ?? old("address2")])
                        </div>
                        <div class="sm:col-span-1">
                            @include("shared.input", ["name" => "zipcode", "label" => __('global.zip'), "value" => auth('web')->user()->zipcode ?? old("zipcode")])
                        </div>
                        <div class="sm:col-span-3">
                            @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => auth('web')->user()->email ?? old("email"), "disabled"=> true])
                        </div>
                        <div class="sm:col-span-3">
                            @include("shared.input", ["name" => "phone", "label" => __('global.phone'), "value" => auth('web')->user()->phone ?? old("phone")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries,"value" => auth('web')->user()->country ?? old("country")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared.input", ["name" => "city", "label" => __('global.city'), "value" => auth('web')->user()->city ?? old("city")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared.input", ["name" => "region", "label" => __('global.region'), "value" => auth('web')->user()->region ?? old("region")])
                        </div>
                        <div class="sm:col-span-2">
                            @include("shared/select", ["name" => "locale", "label" => __('global.locale'), "options" => $locales, "value" => auth('web')->user()->locale ?? old("locale")] )
                        </div>
                        <div class="sm:col-span-4">
                            @include("shared/textarea", ["name" => "billing_details", "label" => __('global.billing_details'), "value" => auth('web')->user()->billing_details ?? old("billing_details"), "help" => __('global.billing_details_help')])
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
                </form>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="font-semibold">{{ __('client.profile.security_question.title') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('client.profile.security_question.description') }}</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('front.profile.security_question') }}">
                        @csrf

                        @if($user->hasSecurityQuestion())
                        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-sm text-green-700 dark:text-green-300">
                                <i class="bi bi-shield-check mr-2"></i>
                                {{ __('client.profile.security_question.current') }}:
                                <strong>{{ $user->securityQuestion->question }}</strong>
                            </p>
                        </div>
                        @endif
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    'placeholder' => __('client.profile.security_question.answer_placeholder'),
                                    'required' => true,
                                ])
                            </div>
                        </div>
                        <div class="mt-4">
                            @include('shared/password', ['name' => 'currentpassword_sq', 'label' => __('client.profile.currentpassword')])

                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save2 mr-2"></i>
                                {{ $user->hasSecurityQuestion() ? __('client.profile.security_question.update') : __('client.profile.security_question.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-span-1">

            <div class="card">
                <div class="card-heading">

                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __('client.profile.security.index') }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('client.profile.security.index_description') }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('front.profile.password') }}">
                    @csrf
                    <div class="grid">

                        <div>
                            @include("shared/password", ["name" => "currentpassword", "label" => __('client.profile.security.currentpassword')])
                        </div>
                        <div>
                            @include("shared/password", ["name" => "password", "label" => __('client.profile.security.newpassword')])
                        </div>
                        <div>
                            @include("shared/password", ["name" => "password_confirmation", "label" => __('global.password_confirmation')])
                        </div>

                        @if (auth('web')->user()->twoFactorEnabled())
                        <div>
                            @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code')])
                        </div>
                        @endif
                        @if($user->hasSecurityQuestion())
                        <div>
                            @include("shared/input", ["name" => "security_answer", "label" => $user->securityQuestion->question])
                        </div>
                        @endif
                    </div>
                    <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>

                </form>

                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mt-2">
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

                <form method="POST" action="{{ route('front.profile.2fa') }}" class="mt-2">
                    @csrf
                    @if (!auth('web')->user()->twoFactorEnabled())
                    {!! $qrcode !!}
                    @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code'), "help" => $code])
                    @else
                    @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code')])
                    @endif
                    <button class="btn {{ auth('web')->user()->twoFactorEnabled() ? 'bg-red-600 text-white' : 'btn-primary' }} mt-4">{{ __(auth('web')->user()->twoFactorEnabled() ? 'global.delete' : 'global.save') }}</button>
                </form>
            </div>


            @foreach ($providers ?? [] as $provider)
            @if ($provider->isSynced())
            <a href="{{ route('socialauth.unlink', $provider->name) }}" class="@if(!$loop->first) mt-2 @endif w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">

                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                {{ __('socialauth::messages.unlink', ['provider' => $provider->provider()->title()]) }}
            </a>
            @else
            <a href="{{ route('socialauth.authorize', $provider->name) }}" class="@if(!$loop->first) mt-2 @endif w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">

                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                {{ __('socialauth::messages.sync_with', ['provider' => $provider->provider()->title()]) }}
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @php
    $deletionService = new \App\Services\Account\AccountDeletionService();
    $canDelete = $deletionService->canDelete($user);
    $blockingReasons = $deletionService->getBlockingReasons($user);
    @endphp

    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-red-700 dark:text-red-400">{{ __('client.profile.delete.danger_zone') }}</h3>
                <p class="mt-2 text-red-600 dark:text-red-300">{{ __('client.profile.delete.warning_message') }}</p>

                @if(!$canDelete)
                <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <p class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">{{ __('client.profile.delete.cannot_delete') }}</p>
                    @if(isset($blockingReasons['active_services']))
                    <p class="text-yellow-700 dark:text-yellow-300">
                        {{ __('client.profile.delete.has_active_services', ['count' => $blockingReasons['active_services']['count']]) }}
                    </p>
                    @endif
                    @if(isset($blockingReasons['pending_invoices']))
                    <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('client.profile.delete.has_pending_invoices', ['count' => $blockingReasons['pending_invoices']['count']]) }}
                    </p>
                    @endif
                </div>
                @else
                <form action="{{ route('front.profile.delete.confirm') }}" method="POST" class="mt-4" onsubmit="return confirm('{{ __('client.profile.delete.final_confirm') }}')">
                    @csrf
                    @method('DELETE')

                    <div class="space-y-4">
                        <div>
                            @include('shared/input', [
                            'name' => 'password',
                            'type' => 'password',
                            'label' => __('client.profile.delete.password_label')
                            ])
                        </div>

                        @if($user->twoFactorEnabled())
                        <div>
                            @include('shared/input', [
                            'name' => '2fa_code',
                            'type' => 'text',
                            'label' => __('client.profile.delete.2fa_label')
                            ])
                        </div>
                        @endif

                        <div>
                            @include('shared/checkbox', [
                            'name' => 'confirm_deletion',
                            'label' => __('client.profile.delete.confirm_checkbox'),
                            'checked' => false
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
@endsection