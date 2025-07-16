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
<label for="{{ $name }}{{ $rand }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}@if(isset($optional)) ({{ __('global.optional') }}) @endif</label>
@endif
<div class="mt-2{{ isset($translatable) && $translatable ? ' flex' : '' }}">
    <input type="{{ $type ?? 'text' }}" @if (isset($readonly)) readonly="" @endif @if (isset($disabled)) disabled="" @endif value="{{ $value ?? old($name)  }}" name="{{ $name }}" id="{{ $name }}{{ $rand }}" @if(isset($step)) step="{{ $step }}" @endif @if(isset($min)) min="{{ $min }}" @endif class="input-text @error($name) border-red-500 @enderror"  @foreach ($attributes ?? [] as $key => $value){{$key}}="{{$value}}" @endforeach>
    @if (isset($translatable) && $translatable)
        <button type="button" class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#translations-overlay-{{ $translatableName ?? $name }}">
            <i class="bi bi-translate"></i>
        </button>
    @endif
    @error($name)
    <span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
    @enderror
</div>

@if (isset($help))
    <p class="text-sm text-gray-500 mt-2">{{ $help }}</p>
@endif
