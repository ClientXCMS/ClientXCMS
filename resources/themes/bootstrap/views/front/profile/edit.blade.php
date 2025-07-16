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
 * Year: 2025
 */
?>
?>
?>
@extends('layouts/client')
@section('title', __('client.profile.index'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/filter.js') }}"></script>
@endsection
@section('content')
    <div class="container py-4">
        @include('shared/alerts')

        <div class="row g-2">
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-body">

                        <h2 class="h5 mb-1 text-dark">
                            {{ __('client.profile.index') }}
                        </h2>
                        <p class="small text-muted">
                            {{ __('client.profile.index_description') }}
                        </p>
                        <form method="POST" action="{{ route('front.profile.update') }}">
                            @csrf
                            <div class="row g-3">

                                <div class="col-md-6">
                                    @include("shared.input", ["name" => "firstname", "label" => __('global.firstname'), "value" => auth('web')->user()->firstname ?? old("firstname")])
                                </div>
                                <div class="col-md-6">
                                    @include("shared.input", ["name" => "lastname", "label" => __('global.lastname'), "value" => auth('web')->user()->lastname ?? old("lastname")])
                                </div>
                                <div class="col-md-6">
                                    @include("shared.input", ["name" => "address", "label" => __('global.address'), "value" => auth('web')->user()->address ?? old("address")])
                                </div>
                                <div class="col-md-4">
                                    @include("shared.input", ["name" => "address2", "label" => __('global.address2'), "value" => auth('web')->user()->address2 ?? old("address2")])
                                </div>
                                <div class="col-md-2">
                                    @include("shared.input", ["name" => "zipcode", "label" => __('global.zip'), "value" => auth('web')->user()->zipcode ?? old("zipcode")])
                                </div>
                                <div class="col-md-6">
                                    @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => auth('web')->user()->email ?? old("email"), "disabled"=> true])
                                </div>
                                <div class="col-md-6">
                                    @include("shared.input", ["name" => "phone", "label" => __('global.phone'), "value" => auth('web')->user()->phone ?? old("phone")])
                                </div>
                                <div class="col-md-4">
                                    @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries,"value" => auth('web')->user()->country ?? old("country")])
                                </div>
                                <div class="col-md-4">
                                    @include("shared.input", ["name" => "city", "label" => __('global.city'), "value" => auth('web')->user()->city ?? old("city")])
                                </div>
                                <div class="col-md-4">
                                    @include("shared.input", ["name" => "region", "label" => __('global.region'), "value" => auth('web')->user()->region ?? old("region")])
                                </div>
                                <div class="col-md-4">
                                    @include("shared/select", ["name" => "locale", "label" => __('global.locale'), "options" => $locales, "value" => auth('web')->user()->locale ?? old("locale")] )
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3">{{ __('global.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">

                        <h2 class="h5 mb-1 text-dark">
                            {{ __('client.profile.security.index') }}
                        </h2>
                        <p class="small text-muted">
                            {{ __('client.profile.security.index_description') }}
                        </p>
                        <form method="POST" action="{{ route('front.profile.password') }}">
                            @csrf
                            <div class="mb-3">
                                @include("shared/password", ["name" => "password", "label" => __('client.profile.security.newpassword')])
                            </div>
                            <div class="mb-3">
                                @include("shared/password", ["name" => "password_confirmation", "label" => __('global.password_confirmation')])
                            </div>
                            <div class="mb-3">
                                @include("shared/password", ["name" => "currentpassword", "label" => __('client.profile.security.currentpassword')])
                            </div>
                            @if (auth('web')->user()->twoFactorEnabled())
                                <div class="mb-3">
                                    @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code')])
                                </div>
                            @endif
                            <button class="btn btn-primary mt-3">{{ __('global.save') }}</button>
                        </form>

                        <h2 class="h6 mt-4 text-dark">
                            {{ __('client.profile.2fa.title') }}
                        </h2>
                        @if (!auth('web')->user()->twoFactorEnabled())
                            <p class="small text-muted">
                                {{ __('client.profile.2fa.info') }}
                            </p>
                        @else
                            <p class="small text-muted">
                                {!! __('client.profile.2fa.download_codes', ['url' => route('front.profile.2fa_codes')]) !!}
                            </p>
                        @endif
                        <form method="POST" action="{{ route('front.profile.2fa') }}" class="mt-3">
                            @csrf
                            @if (!auth('web')->user()->twoFactorEnabled())
                                {!! $qrcode !!}
                                @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code'), "help" => $code])
                            @else
                                @include("shared/input", ["name" => "2fa", "label" => __('client.profile.2fa.code')])
                            @endif
                            <button class="btn {{ auth('web')->user()->twoFactorEnabled() ? 'btn-danger' : 'btn-primary' }} mt-3">{{ __(auth('web')->user()->twoFactorEnabled() ? 'global.delete' : 'global.save') }}</button>
                        </form>
                    </div>
                </div>

                @foreach ($providers ?? [] as $provider)
                    <div class="col-12 col-md-6">
                        @if ($provider->isSynced())
                            <a href="{{ route('socialauth.unlink', $provider->name) }}"
                               class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center mt-2">
                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}"
                                     class="me-2" style="width: 20px; height: 20px;">
                                {{ __('socialauth::messages.unlink', ['provider' => $provider->provider()->title()]) }}
                            </a>
                        @else
                            <a href="{{ route('socialauth.authorize', $provider->name) }}"
                               class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center mt-2">
                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}"
                                     class="me-2" style="width: 20px; height: 20px;">
                                {{ __('socialauth::messages.register_with', ['provider' => $provider->provider()->title()]) }}
                            </a>
                        @endif
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endsection
