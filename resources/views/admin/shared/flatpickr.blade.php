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
<label for="{{ $name }}{{ $rand }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}</label>
@endif
<div class="mt-2">
    <input type="{{ $type ?? 'datetime' }}" @if (isset($disabled)) disabled="" @endif value="{{ $value ?? old($name)  }}" name="{{ $name }}" id="{{ $name }}{{ $rand }}" autocomplete="given-name" class="flatpickr input-text @error($name) border-red-500 @enderror">
    @error($name)
    <span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
    @enderror
    @if (isset($help))
        <p class="text-sm text-gray-500 mt-2">{{ $help }}</p>
    @endif
</div>
