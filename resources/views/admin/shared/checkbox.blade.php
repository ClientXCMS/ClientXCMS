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


@if (isset($value) && isset($checked) == false)
    @php
        $checked = $value == 'true';
    @endphp
@endif
@php
    $rand = rand(1, 999);
    // v2.16 — preserve the user's choice after a validation error. We rely
    // on session()->hasOldInput() to distinguish "first GET" (use $checked
    // as-is) from "validation re-render" (use the submitted value, even
    // when it is null because an unchecked checkbox is never POSTed).
    if (session()->hasOldInput()) {
        $__old = session()->getOldInput($name);
        $checked = $__old !== null && in_array((string) $__old, ['true', '1', 'on'], true);
    }
@endphp

    <div class="flex">
        <input type="checkbox" value="{{ $value ?? 'true' }}"
               class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
               id="{{ $name }}{{ $rand }}" name="{{ $name }}" {{ $checked ?? false ? 'checked' : '' }}>
        @if ($label)
        <label for="{{ $name }}{{ $rand }}" class="text-sm text-gray-500 ms-3 dark:text-gray-400">{{ $label }}</label>
        @endif
        @if ($errors->has($name))
            <div class="invalid-feedback">{{ $errors->first($name) }}</div>
        @endif
    </div>
