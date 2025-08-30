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

@extends('install.layout')
@section('title', __('install.register.title'))
@section('content')
    <form method="POST" action="{{ route('install.register') }}">
        @csrf
    @include('shared.alerts')

        <div class="mt-2">
            @include('shared.input', ['name' => 'firstname', 'type' => 'text', 'label' => __('install.firstname')])
        </div>

        <div class="mt-2">
            @include('shared.input', ['name' => 'lastname', 'type' => 'text', 'label' => __('install.lastname')])
        </div>

        <div class="mt-2">
            @include('shared.input', ['name' => 'email', 'type' => 'email', 'label' => __('install.email')])
        </div>

        <div class="mt-2">
            @include('shared.password', ['name' => 'password', 'type' => 'password', 'label' => __('install.password')])
        </div>

        <div class="mt-2">
            @include('shared.password', ['name' => 'password_confirmation', 'label' => __('install.password_confirmation')])
        </div>
        @include('shared.checkbox', ['name' => 'send_telemetry', 'label' => __('install.register.telemetry')])
        <button type="submit" class="mt-4 btn btn-primary w-full">
            {{ __('install.register.btn') }}
        </button>
    </form>
    @endsection
