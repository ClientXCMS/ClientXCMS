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

<label for="range-{{ $option->key }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $option->name }}</label>
@foreach ($options as $_option)
    <div class="flex mt-1">
        <input type="radio" name="options[{{ $option->key }}]" {{ $value == $_option->value ? 'checked' : '' }} value="{{ $_option->value }}" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" id="options[{{ $_option->id }}]" data-radio-id="{{ $_option->id }}" data-radio-label="{{ $_option->friendly_name }}">
        <label for="options[{{ $_option->id }}]" class="text-sm text-gray-500 ms-2 dark:text-neutral-400">{{ $_option->friendly_name }}</label>
    </div>
@endforeach
