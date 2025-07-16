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

@csrf
<div class="row g-3">
    <div class="col-12">
        <label for="email" class="form-label">{{ trans("global.email") }}</label>
        @include("shared/input", ["name" => "email", "type" => "email"])
    </div>
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label">{{ trans("global.password") }}</label>
            <a class="text-decoration-none text-primary fw-medium" href="{{ route($forgotPasswordRoute ?? 'password.email') }}">{{ __('auth.forgot.forgot_password') }}</a>
        </div>
        <div class="position-relative">
            @include("shared/input", ["name" => "password", "type" => "password"])
        </div>
    </div>
    <div class="col-12">
        <div class="form-check">
            @include('shared/checkbox', ['label' => __('auth.login.remember'), 'name' => 'remember'])
        </div>
    </div>
    @if (isset($redirect))
        <input type="hidden" name="redirect" value="{{ $redirect }}">
    @endif
    @if (isset($captcha))
        @include('shared/captcha')
    @endif
    <div class="col-12">
        <button type="submit" class="btn btn-primary w-100">
            {{ __('auth.login.login') }}</button>
    </div>
</div>
