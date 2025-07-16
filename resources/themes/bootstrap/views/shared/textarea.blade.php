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
@php $rand = rand(1, 999); @endphp
@if(isset($label))
    <label for="{{ $name }}{{ $rand }}" class="form-label mt-2">
        {{ $label }}
    </label>
@endif

<div class="mt-2 {{ isset($translatable) && $translatable ? 'd-flex align-items-center' : '' }}">
    <textarea
        @if (isset($disabled)) disabled @endif
    @if (isset($rows)) rows="{{ $rows }}" @endif
        name="{{ $name }}"
        id="{{ $name }}{{ $rand }}"
        rows="{{ $rows ?? 3 }}"
        class="form-control @error($name) is-invalid @enderror"
    >@if (isset($Inverifiedvalue)) {!! $Inverifiedvalue !!}@else{{ $value ?? old($name) }}@endif</textarea>

    @if (isset($translatable) && $translatable)
        <button
            type="button"
            class="btn btn-primary ms-2"
            data-bs-toggle="modal"
            data-bs-target="#translations-overlay-{{ $name }}"
        >
            <i class="bi bi-translate"></i>
        </button>
    @endif

    @error($name)
    <div class="invalid-feedback">
        {{ $message }}
    </div>
    @enderror
</div>

@if (isset($help))
    <p class="form-text mt-2">{{ $help }}</p>
@endif
