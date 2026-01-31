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
 * Year: 2026
 */
?>

@extends('admin/layouts/admin')
@section('title', __('personalization.sections.config.title'))

@php
    $currentLocale = app()->getLocale();
@endphp

@section('content')
<div class="container mx-auto">

    @include('admin/shared/alerts')

    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <form id="section-config-form" method="POST" action="{{ route('admin.personalization.sections.config.update', ['section' => $section]) }}" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf

                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('personalization.sections.config.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('personalization.sections.config.subheading', ['name' => $section->uuid]) }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center space-x-2 sm:mt-0">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                                <a href="{{ route('admin.personalization.sections.index') }}?active_page={{ request()->get('active_page', $section->url) }}" class="btn btn-secondary">
                                    {{ __('global.back') }}
                                </a>
                            </div>
                        </div>

                        <div class="card-body">
                            @if(empty($fields))
                                <div class="p-4 text-gray-500 dark:text-gray-400">
                                    {{ __('personalization.sections.config.no_fields') }}
                                </div>
                            @else
                                <div class="grid gap-4">
                                    @foreach($fields as $field)
                                        @php
                                            $fieldKey = $field['key'];
                                            $fieldType = $field['type'] ?? 'text';
                                            $isTranslatable = $field['translatable'] ?? false;
                                            $fieldValue = $values[$fieldKey] ?? $field['default'] ?? null;

                                            if ($isTranslatable && is_array($fieldValue)) {
                                                $displayValue = $fieldValue[$currentLocale] ?? $fieldValue[array_key_first($fieldValue)] ?? '';
                                            } else {
                                                $displayValue = $fieldValue;
                                            }

                                            $fieldLabel = __($field['label'] ?? $fieldKey);
                                            $fieldHelp = isset($field['hint']) ? __($field['hint']) : null;
                                            $fieldName = $isTranslatable ? "{$fieldKey}[{$currentLocale}]" : $fieldKey;
                                        @endphp

                                        <div>
                                            @if($fieldType === 'text' || $fieldType === 'url')
                                                @include('admin/shared/input', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldName,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'type' => $fieldType === 'url' ? 'url' : 'text',
                                                    'translatable' => $isTranslatable,
                                                    'translatableName' => $fieldKey,
                                                ])

                                            @elseif($fieldType === 'textarea')
                                                @include('admin/shared/textarea', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldName,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'rows' => $field['rows'] ?? 3,
                                                    'translatable' => $isTranslatable,
                                                ])

                                            @elseif($fieldType === 'number')
                                                @include('admin/shared/input', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldName,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'type' => 'number',
                                                    'min' => $field['min'] ?? null,
                                                    'max' => $field['max'] ?? null,
                                                    'step' => $field['step'] ?? null,
                                                ])

                                            @elseif($fieldType === 'boolean')
                                                @include('admin/shared/checkbox', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldKey,
                                                    'checked' => filter_var($displayValue, FILTER_VALIDATE_BOOLEAN),
                                                ])

                                            @elseif($fieldType === 'select')
                                                @php
                                                    $selectOptions = [];
                                                    foreach ($field['options'] ?? [] as $optKey => $optVal) {
                                                        $key = is_numeric($optKey) ? $optVal : $optKey;
                                                        $label = is_array($optVal) ? ($optVal['label'] ?? $key) : $optVal;
                                                        $selectOptions[$key] = $label;
                                                    }
                                                @endphp
                                                @include('admin/shared/select', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldKey,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'options' => $selectOptions,
                                                ])

                                            @elseif($fieldType === 'color')
                                                @include('admin/shared/color', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldKey,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                ])

                                            @elseif($fieldType === 'icon')
                                                @include('admin/shared/icon', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldKey,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                ])

                                            @elseif($fieldType === 'image')
                                                <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">
                                                    {{ $fieldLabel }}
                                                </label>
                                                @if($displayValue)
                                                    <div class="mt-2 flex items-start gap-3">
                                                        <img src="{{ Storage::url($displayValue) }}" alt="" class="max-w-xs max-h-24 rounded border border-gray-200 dark:border-gray-700">
                                                        <label class="flex items-center gap-2 text-sm text-red-500 cursor-pointer">
                                                            <input type="checkbox" name="remove_{{ $fieldKey }}" value="1" class="rounded">
                                                            {{ __('personalization.sections.fields_ui.remove_image') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                @include('admin/shared/file', [
                                                    'name' => $fieldKey,
                                                    'help' => $fieldHelp,
                                                ])

                                            @elseif($fieldType === 'repeater')
                                                @include('admin/shared/repeater', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldKey,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'fields' => $field['fields'] ?? $field['subfields'] ?? [],
                                                    'min' => $field['min'] ?? 0,
                                                    'max' => $field['max'] ?? 10,
                                                ])

                                            @else
                                                @include('admin/shared/input', [
                                                    'label' => $fieldLabel,
                                                    'name' => $fieldName,
                                                    'value' => $displayValue,
                                                    'help' => $fieldHelp,
                                                    'translatable' => $isTranslatable,
                                                    'translatableName' => $fieldKey,
                                                ])
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($fields as $field)
    @if($field['translatable'] ?? false)
        @php
            $fieldKey = $field['key'];
            $fieldType = $field['type'] ?? 'text';
            $fieldValue = $values[$fieldKey] ?? [];
            if (!is_array($fieldValue)) {
                $fieldValue = [$currentLocale => $fieldValue];
            }
        @endphp
        <div id="translations-overlay-{{ $fieldKey }}" class="hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __('admin.locales.subheading') }} - {{ __($field['label'] ?? $fieldKey) }}
                </h3>
                <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-{{ $fieldKey }}">
                    <span class="sr-only">{{ __('global.closemodal') }}</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                @foreach($locales as $localeKey => $localeData)
                    <div>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            @if(isset($localeData['flag']))
                                <img src="{{ $localeData['flag'] }}" alt="{{ $localeKey }}" class="w-5 h-4">
                            @endif
                            {{ $localeData['name'] ?? strtoupper($localeKey) }}
                        </label>
                        @if($fieldType === 'textarea')
                            <textarea
                                form="section-config-form"
                                name="{{ $fieldKey }}[{{ $localeKey }}]"
                                rows="3"
                                class="input-text w-full"
                            >{{ $fieldValue[$localeKey] ?? '' }}</textarea>
                        @else
                            <input type="text"
                                   form="section-config-form"
                                   name="{{ $fieldKey }}[{{ $localeKey }}]"
                                   value="{{ $fieldValue[$localeKey] ?? '' }}"
                                   class="input-text w-full">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
@endsection
