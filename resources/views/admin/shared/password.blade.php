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

@php
    $fieldId = $id ?? preg_replace('/[^A-Za-z0-9_-]/', '', $name);
    $usedFieldIds = request()->attributes->get('admin_password_field_ids', []);
    for ($suffix = 2, $baseId = $fieldId; in_array($fieldId, $usedFieldIds, true); $suffix++) {
        $fieldId = $baseId.'-'.$suffix;
    }
    request()->attributes->set('admin_password_field_ids', [...$usedFieldIds, $fieldId]);
    $hasError = isset($errors) && $errors->has($name);
    $describedBy = trim(($hasError ? $fieldId.'-error ' : '').(isset($help) ? $fieldId.'-help' : ''));
@endphp
    @if(isset($label))

    <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2" for="{{ $fieldId }}">{{ $label }}@if(isset($optional)) ({{ __('global.optional') }}) @endif</label>
    @endif
    <div class="relative mt-2">
        <input id="{{ $fieldId }}" name="{{ $name }}" type="password" class="input-text input-password @if($hasError) border-red-500 @endif" value="{{ old($name, $value ?? '') }}" @if($hasError) aria-invalid="true" @endif @if($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif @foreach (is_array($attributes ?? null) ? $attributes : [] as $attributeName => $attributeValue) {{ $attributeName }}="{{ $attributeValue }}" @endforeach>
        <button type="button" data-hs-toggle-password='{
        "target": "#{{ $fieldId }}"
      }' class="absolute top-0 end-0 p-3.5 rounded-e-md" aria-label="{{ __('admin.toggle_password_visibility') }}" aria-controls="{{ $fieldId }}" aria-pressed="false">
            <svg aria-hidden="true" focusable="false" class="flex-shrink-0 size-3.5 text-gray-500 dark:text-neutral-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path class="hs-password-active:hidden" d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                <path class="hs-password-active:hidden" d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                <path class="hs-password-active:hidden" d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                <line class="hs-password-active:hidden" x1="2" x2="22" y1="2" y2="22"></line>
                <path class="hidden hs-password-active:block" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                <circle class="hidden hs-password-active:block" cx="12" cy="12" r="3"></circle>
            </svg>
        </button>
    </div>
@if($hasError)
<p id="{{ $fieldId }}-error" class="mt-2 text-sm text-red-700 dark:text-red-400">
    {{ $errors->first($name) }}
</p>
@endif
@if (isset($help))
    <p id="{{ $fieldId }}-help" class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $help }}</p>
@endif
@if (isset($generate))
    <button class="text-sm text-gray-500 dark:text-gray-400 mt-2 cursor-pointer generate-password-btn" type="button">{{ __('global.password_generate') }}</button>
@endif
