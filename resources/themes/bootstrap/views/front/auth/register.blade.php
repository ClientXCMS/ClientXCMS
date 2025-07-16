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
@php($container = 'col-12 col-sm-8 offset-sm-4 col-md-8 offset-md-4 col-lg-8 offset-lg-4 col-xl-6 offset-xl-3')
@extends('layouts/auth')
@section('title', __('auth.register.title'))
@section('redirect')

    <p class="text-md text-muted mb-0">
        {{ __('auth.register.already') }}
        <a class="text-primary" href="{{ route('login') }}">
            {{ __('auth.login.login') }}
        </a>
    </p>
@endsection
@section('content')
        @include('shared.alerts')
        @if ($providers->isNotEmpty())
            <div class="row mt-2">

                @foreach ($providers as $provider)
                    <div class="col-12 col-md-6">
                        <a href="{{ route('socialauth.authorize', $provider->name) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                            <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="me-2" style="width: 20px; height: 20px;">
                            {{ __('socialauth::messages.register_with', ['provider' => $provider->provider()->title()]) }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="row mt-2">
                @include('shared.auth.register')
        </div>
@endsection
