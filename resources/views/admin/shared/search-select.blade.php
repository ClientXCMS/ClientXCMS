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


<label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}</label>
<div class="mt-2 relative">
    <select name="{{ $name }}" id="{{ $name }}" data-hs-select='{
      "hasSearch": true,
      "searchPlaceholder": "{{ __('global.search') }}",
       "placeholder": "{{ __('global.select') }}",
       "searchId": "{{ $name }}-search",
       "searchClasses": "{{ $name }}-search block w-full text-sm border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 before:absolute before:inset-0 before:z-[1] dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 py-2 px-3",
      "searchWrapperClasses": "bg-white border dark:border-none p-2 -mx-1 sticky top-0 dark:bg-slate-900",
      "toggleTag": "<button type=\"button\"><span class=\"text-gray-800 dark:text-gray-200\" data-title></span></button>",
      "toggleClasses": "border dark:border-none hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative text-nowrap cursor-pointer input-text before:absolute before:inset-0 before:z-[1]",
      "dropdownClasses": "mt-2 max-h-[300px] pb-1 px-1 space-y-0.5 z-20 w-full bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto dark:bg-slate-900 dark:border-gray-700",
      "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 dark:bg-slate-900 dark:hover:bg-slate-800 dark:text-gray-200 dark:focus:bg-slate-800",
      "optionTemplate": "<div><div class=\"flex items-center\"><div class=\"me-2\" data-icon></div><div class=\"text-gray-800 dark:text-gray-200\" data-title></div></div></div>"
    }' class="hidden"  @foreach($attributes ?? [] as $k => $v) {{ $k }}="{{ $v }}"@endforeach>

        @foreach($options as $_value => $option)
            <option value="{{ $_value }}"{{ $value == $_value || old($name) == $_value ? ' selected' : '' }}>{{ $option }}</option>
        @endforeach
    </select>
    {!! $buttonHTML ?? '' !!}
    @error($name)
    <span class="mt-2 text-sm text-red-500">
            {{ $message }}
        </span>
    @enderror

        @if (isset($help))
            <p class="text-sm text-gray-500 mt-2">{{ $help }}</p>
        @endif
</div>
