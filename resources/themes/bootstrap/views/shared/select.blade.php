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
@if (isset($label))
    <label for="{{ $name }}" class="form-label mt-2">{{ $label }}</label>
@endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-select @error($name) is-invalid @enderror"
    @foreach($attributes ?? [] as $k => $v) {{ $k }}="{{ $v }}" @endforeach
    >
    @foreach($options as $_value => $option)
        <option
            value="{{ $_value }}"
        {{ $value == $_value || old($name) == $_value ? 'selected' : '' }}
        @foreach($options_attributes[$_value] ?? [] as $k => $v) {{ $k }}="{{ $v }}" @endforeach
        >
        {{ $option }}
        </option>
        @endforeach
        </select>
        @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror

        @if (isset($help))
            <p class="form-text mt-2">{{ $help }}</p>
        @endif
