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
@if(isset($label))
    <label for="{{ $name }}" class="form-label visually-hidden">{{ $label }}</label>
@endif
<input
    type="file"
    multiple
    class="form-control @error($name) is-invalid @enderror mt-2"
    name="{{ $name }}[]"
/>
@error($name)
<div class="invalid-feedback">
    {{ $message }}
</div>
@enderror

@if (isset($help))
    <div class="form-text">{{ $help }}</div>
@endif

@for ($i = 0; $i < 6; $i++)
    @error("attachments.$i")
    <div class="invalid-feedback d-block">
        {{ $message }}
    </div>
    @enderror
@endfor
