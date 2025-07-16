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
    <label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">{{ $label }}</label>
@endif
<input
    type="file"
    multiple
    label="{{ __('global.drop-file.label') }}"
    help="{{ __('global.drop-file.help', ['extensions' => $extensions ?? 'jpg,png, jpeg']) }}"
    name="{{ $name }}"
    is="drop-files"
/>    @error($name)
<span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
@enderror
@if (isset($help))
    <p class="text-sm text-gray-500 mt-2">{{ $help }}</p>
@endif
@if (isset($canRemove))
<div class="flex">
    <input type="checkbox" value="true"
           class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
           id="remove_{{ $name }}" name="remove_{{ $name }}" {{ $checked ?? false ? 'checked' : '' }}>
    <label for="remove_{{ $name }}" class="text-sm text-gray-500 ms-3 dark:text-gray-400">{{ __('global.clicktoremove') }}</label>
</div>
@endif
