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

@php $rand = rand(1, 999); @endphp
    @if(isset($label))

    <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2" for="{{ $name }}{{ $rand }}">{{ $label }}@if(isset($optional)) ({{ __('global.optional') }}) @endif</label>
    @endif
    <div class="relative mt-2">
        <input id="{{ $name }}{{ $rand }}" name="{{ $name }}" type="password" class="input-text input-password @error($name) border-red-500 @enderror" value="{{ $value ?? old($name) }}">
        <button type="button" data-hs-toggle-password='{
        "target": "#{{ $name }}{{ $rand }}"
      }' class="absolute top-0 end-0 p-3.5 rounded-e-md">
            <svg class="flex-shrink-0 size-3.5 text-gray-400 dark:text-neutral-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path class="hs-password-active:hidden" d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                <path class="hs-password-active:hidden" d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                <path class="hs-password-active:hidden" d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                <line class="hs-password-active:hidden" x1="2" x2="22" y1="2" y2="22"></line>
                <path class="hidden hs-password-active:block" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                <circle class="hidden hs-password-active:block" cx="12" cy="12" r="3"></circle>
            </svg>
        </button>
    </div>
@error($name)

<span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
@enderror
@if (isset($help))
    <p class="text-sm text-gray-500 mt-2">{{ $help }}</p>
@endif
@if (isset($generate))
    <button class="text-sm text-gray-500 mt-2 cursor-pointer generate-password-btn" type="button">{{ __('global.password_generate') }}</button>
@endif
