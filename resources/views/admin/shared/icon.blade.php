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
 * Year: 2026
 */
?>

@php $rand = rand(1, 999); @endphp
@if(isset($label))
<label for="{{ $name }}{{ $rand }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}
    @if (isset($help))
    <div class="hs-tooltip inline-block">
        <button type="button" class="hs-tooltip-toggle">
            <i class="bi bi-info-circle-fill text-gray-500 dark:text-gray-400"></i>
            <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white" role="tooltip">
                {{ $help }}
            </span>
        </button>
    </div>
    @endif
</label>
@endif
<div class="mt-2 flex items-center gap-2">
    <div id="{{ $name }}_preview{{ $rand }}" class="w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-xl text-gray-700 dark:text-gray-300">
        <i class="{{ $value ?? old($name) ?? 'bi-star' }}"></i>
    </div>
    <input type="text"
        name="{{ $name }}"
        id="{{ $name }}{{ $rand }}"
        value="{{ $value ?? old($name) }}"
        placeholder="bi-star"
        class="input-text flex-1 @error($name) border-red-500 @enderror"
        oninput="document.getElementById('{{ $name }}_preview{{ $rand }}').innerHTML = '<i class=\'' + this.value + '\'></i>'"
        @foreach ($attributes ?? [] as $key=> $attrValue){{ $key }}="{{ $attrValue }}" @endforeach>
</div>
<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
    {{ __('personalization.icon_help', ['url' => 'https://icons.getbootstrap.com/']) }}
</p>
@error($name)
<span class="mt-2 text-sm text-red-500">{{ $message }}</span>
@enderror