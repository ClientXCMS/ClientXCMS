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

@extends('layouts/auth')
@section('title', __('client.profile.2fa.heading'))
@section('content')
@php
    $step = $factorStep ?? 'totp';
    $isTwoStep = ($requiresEmailAfter ?? false) || ($requiresTotpBefore ?? false);
    $isTotpStep = $step === 'totp';
    $trustDays = (int) setting('trust_device_days', 30);
@endphp
<div class="p-4 sm:p-7">
    <header class="text-center">
        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">
            {{ __('client.profile.2fa.heading') }}
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            @if ($isTwoStep && $isTotpStep)
                {{ __('client.profile.2fa.step1_subheading') }}
            @elseif ($isTwoStep)
                {{ __('client.profile.2fa.step2_subheading') }}
            @else
                {{ __('client.profile.2fa.subheading') }}
            @endif
        </p>
    </header>

    @if ($isTwoStep)
        <nav aria-label="{{ __('client.profile.2fa.heading') }}" class="mt-6">
            <ol class="flex items-center justify-center gap-3 text-xs font-medium">
                <li class="flex items-center gap-2" @if($isTotpStep) aria-current="step" @endif>
                    <span aria-hidden="true" class="inline-flex h-6 w-6 items-center justify-center rounded-full {{ $isTotpStep ? 'bg-blue-600 text-white' : 'bg-green-600 text-white' }}">
                        @if ($isTotpStep) 1 @else <i class="bi bi-check-lg"></i> @endif
                    </span>
                    <span class="{{ $isTotpStep ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('client.profile.2fa.step_indicator_totp') }}
                    </span>
                </li>
                <li class="h-px w-8 bg-gray-300 dark:bg-gray-700" aria-hidden="true"></li>
                <li class="flex items-center gap-2" @if(! $isTotpStep) aria-current="step" @endif>
                    <span aria-hidden="true" class="inline-flex h-6 w-6 items-center justify-center rounded-full {{ ! $isTotpStep ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">2</span>
                    <span class="{{ ! $isTotpStep ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('client.profile.2fa.step_indicator_email') }}
                    </span>
                </li>
            </ol>
        </nav>
    @endif

    @include('shared.alerts')

    @if ($isTotpStep)
        <form method="POST" action="{{ route('auth.2fa') }}" id="captcha-form" class="mt-5" novalidate>
            @csrf
            @include('shared.input', [
                'name' => '2fa',
                'type' => 'text',
                'label' => __('client.profile.2fa.code_totp'),
                'attributes' => [
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'text',
                    'autofocus' => 'autofocus',
                    'autocapitalize' => 'off',
                    'spellcheck' => 'false',
                    'aria-describedby' => '2fa-totp-hint',
                ],
            ])
            <p id="2fa-totp-hint" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('client.profile.2fa.code_totp_hint') }}
            </p>
            @include('shared.captcha')
            <button type="submit" class="btn-primary block w-full mt-5 min-h-[44px]">
                {{ $isTwoStep ? __('client.profile.2fa.next_step') : __('auth.login.login') }}
            </button>
        </form>
    @else
        <div
            class="mt-5 flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-200"
            role="status">
            <i class="bi bi-envelope-check mt-0.5 flex-shrink-0" aria-hidden="true"></i>
            <span>{{ __('client.profile.2fa.email_sent_to', ['email' => $maskedEmail ?? '']) }}</span>
        </div>

        <form method="POST" action="{{ route('auth.2fa') }}" id="captcha-form" class="mt-5" novalidate>
            @csrf
            @include('shared.input', [
                'name' => '2fa',
                'type' => 'text',
                'label' => __('client.profile.2fa.code_email'),
                'attributes' => [
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{6}',
                    'maxlength' => '6',
                    'autofocus' => 'autofocus',
                ],
            ])

            <label class="mt-4 flex cursor-pointer items-start gap-3 select-none">
                <input type="checkbox" name="trust_device" value="1"
                       class="mt-0.5 h-5 w-5 flex-shrink-0 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-800">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('client.profile.2fa.trust_device', ['days' => $trustDays]) }}
                </span>
            </label>

            @include('shared.captcha')
            <button type="submit" class="btn-primary block w-full mt-5 min-h-[44px]">
                {{ __('auth.login.login') }}
            </button>
        </form>

        <div class="mt-4 flex flex-col items-center gap-2 text-sm">
            <form method="POST" action="{{ route('auth.2fa.email') }}" class="contents">
                @csrf
                <button type="submit"
                        class="text-blue-700 underline-offset-2 hover:underline focus-visible:underline focus-visible:outline-none dark:text-blue-300">
                    {{ __('client.profile.2fa.resend_email_code') }}
                </button>
            </form>

            @if ($requiresTotpBefore ?? false)
                <form method="POST" action="{{ route('auth.2fa.reset') }}" class="contents">
                    @csrf
                    <button type="submit"
                            class="text-gray-500 underline-offset-2 hover:underline focus-visible:underline focus-visible:outline-none dark:text-gray-400">
                        <i class="bi bi-arrow-left mr-1" aria-hidden="true"></i>{{ __('client.profile.2fa.back_to_step1') }}
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
