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
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
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
    </div>
@endsection
