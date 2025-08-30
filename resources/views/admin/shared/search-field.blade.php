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
$rand = rand(1000, 9999); ?>

@if (isset($label))
    <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">
        {{ $label }}
    </label>
@endif

<div class="mt-2">
    <select
        name="{{ $name }}"
        id="{{ $name }}{{ $rand }}"
        @if (isset($disabled)) disabled @endif
        @if (isset($readonly)) readonly @endif
        data-placeholder="{{ $searchPlaceholder ?? __('global.search') }}"
        data-apiurl="{{ $apiUrl }}"
        autocomplete="off"
        class="tom-select w-full h-full"
        data-default-value="{{ $value ?? '' }}"
    >
        @if (isset($options))
            @foreach ($options as $v => $label)
                <option value="{{ $v }}" @if (isset($value) && $value == $v) selected @endif>
                    {{ $label }}
                </option>
            @endforeach
        @endif
    </select>
</div>
