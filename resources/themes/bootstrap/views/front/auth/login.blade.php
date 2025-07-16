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
@extends('layouts/auth')
@section('title', __('auth.login.title'))
@section('redirect')
    <p class="text-md mb-0 text-muted">
        {{ __('auth.login.no_account') }}
        <a class="text-primary" href="{{ route('register') }}">
            {{ __('auth.register.register') }}
        </a>
    </p>
    @endsection
@section('content')
        @include('shared.alerts')
            @if ($providers->isNotEmpty())
            <div class="row mt-2">

                @foreach ($providers as $provider)
                    <div class="col-12 col-md-12">
                        <a href="{{ route('socialauth.authorize', $provider->name) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                            <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="me-2" style="width: 20px; height: 20px;">
                            {{ __('socialauth::messages.login_with', ['provider' => $provider->provider()->title()]) }}
                        </a>
                    </div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-2">
                @include('shared.auth.login', ['captcha' => true])
            </form>
@endsection
