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

@if (isset($value) && isset($checked) == false)
    @php
        $checked = $value == 'true';
    @endphp
@endif
@php $rand = rand(1, 999); @endphp

<div class="form-check">
    <input
        type="checkbox"
        value="{{ $value ?? 'true' }}"
        class="form-check-input @error($name) is-invalid @enderror"
        id="{{ $name }}{{ $rand }}"
        name="{{ $name }}"
        {{ $checked ?? false ? 'checked' : '' }}
    >
    @if ($label)
        <label for="{{ $name }}{{ $rand }}" class="form-check-label">
            {{ $label }}
        </label>
    @endif
    @if ($errors->has($name))
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
</div>
