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

@if (isset($label))
    <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}
        @if (isset($help))

            <div class="hs-tooltip inline-block">
                <button type="button" class="hs-tooltip-toggle">
                    <i class="bi bi-info-circle-fill text-gray-500 dark:text-gray-400"></i>
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white" role="tooltip">
      {{ $help }}
    </span>
                </button>
            </div>
        @endif</label>
@endif
<div class="mt-2">
    <select name="{{ $name }}" id="{{ $name }}" class="input-text" @foreach($attributes ?? [] as $k => $v) {{ $k }}="{{ $v }}"@endforeach >
        @foreach($options as $_value => $option)
            <option value="{{ $_value }}"{{ $value == $_value || old($name) == $_value ? ' selected' : '' }} @foreach($options_attributes[$_value] ?? [] as $k => $v) {{ $k }}="{{ $v }}"@endforeach >{{ $option }}</option>
        @endforeach
    </select>
    @error($name)
    <span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
    @enderror
</div>
