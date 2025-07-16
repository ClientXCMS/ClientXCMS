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
@section('title', __('auth.reset.title'))
@section('redirect')

    <p class="mb-0 text-muted text-md">
        {{ __('auth.register.already') }}
        <a class="text-primary text-decoration-none fw-medium" href="{{ route('login') }}">
            {{ __('auth.login.login') }}
        </a>
    </p>
    @endsection

@section('content')
        @include('shared.alerts')

    <form method="POST" action="{{ route('password.store') }}" class="mt-2">
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        @csrf
            @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => $request->get('email'), "class" => "form-control"])
            @include("shared.input", ["name" => "password", "label" => __('global.password'), "type" => "password", "class" => "form-control"])
            @include("shared.input", ["name" => "password_confirmation", "label" => __('global.password_confirmation'), "type" => "password", "class" => "form-control"])
            @include('shared.captcha')

            <button class="btn btn-primary w-100 mt-2">
                {{ __('auth.reset.btn') }}
            </button>
        </div>
    </form>
@endsection
