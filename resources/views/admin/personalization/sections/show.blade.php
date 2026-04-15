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

@extends('admin/layouts/admin')
@section('title', __('personalization.sections.show.title'))
@section('styles')
<link rel="stylesheet" href="{{ Vite::asset('resources/global/css/monaco-editor.main.css') }}">
@endsection
@section('scripts')
<script src="{{ Vite::asset('resources/global/js/admin/sections.js') }}" type="module"></script>
<script>
    window.sections = {
        value: @json(old('content', $content)),
        theme: {!! !is_darkmode(true) ? "'vs'" : "'vs-dark'" !!}
    }
</script>
@endsection
@section('content')
<div class="container mx-auto">

    @include('admin/shared/alerts')
    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">

                @if ($item->isConfigurable())
                <form id="section-config-form" method="POST" action="{{ route('admin.personalization.sections.config.update', ['section' => $section]) }}" enctype="multipart/form-data" class="mt-4">
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
                            </div>
                        </div>

                        <div class="card-body">
                            {!! $item->toDTO()->render() !!}
                            @if(empty($fields))
                            <div class="p-4 text-gray-500 dark:text-gray-400">
                                {{ __('personalization.sections.config.no_fields') }}
                            </div>
                            @else
                            <div class="grid gap-4">
                                @foreach($fields as $field)
                                @include('admin.personalization.sections.includes.field', ['field' => $field, 'values' => $values, 'locales' => $locales])
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.personalization.sections.update', ['section' => $item]) }}" id="section-form">
                    @method('PUT')
                    @csrf
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('personalization.sections.show.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('personalization.sections.show.subheading') }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center space-x-1 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                                <a href="{{ route('admin.personalization.sections.index') }}" class="btn btn-secondary">
                                    {{ __('global.back') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="grid gap-4">
                                @if (app()->isLocal())
                                <div>
                                    @include('admin/shared/input', [
                                    'label' => 'UUID',
                                    'name' => 'uuid',
                                    'value' => $section->uuid,
                                    'disabled' => true
                                    ])
                                </div>
                                @endif
                                <div>
                                    @include('admin/shared/select', [
                                    'label' => __('personalization.theme.themename'),
                                    'name' => 'theme_uuid',
                                    'value' => $item->theme_uuid,
                                    'options' => $themes
                                    ])
                                </div>

                                <div>
                                    @include('admin/shared/select', [
                                    'label' => __('personalization.sections.fields.url'),
                                    'name' => 'url',
                                    'value' => $item->url,
                                    'options' => $pages
                                    ])
                                </div>
                                @if (!$item->toDTO()->isProtected())
                                <div>
                                    <input type="hidden" name="content" value="{{ old('content', $content) }}">
                                    <div id="monaco-editor" style="height: 400px;"></div>
                                </div>
                                @error('content')
                                <div class="bg-red-100 dark:bg-red-900/30 p-4 rounded">
                                    <p class="text-red-600 dark:text-red-400">
                                        {{ $message }}
                                    </p>
                                </div>
                                @enderror
                                @endif
                            </div>
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
$fieldValue = [current_locale() => $fieldValue];
}
@endphp
<div id="translations-overlay-{{ $fieldKey }}" class="hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
        <h3 class="font-bold text-gray-800 dark:text-white">
            {{ __('admin.locales.subheading') }} - {{ __($field['label'] ?? $fieldKey) }}
        </h3>
        <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-{{ $fieldKey }}">
            <span class="sr-only">{{ __('global.closemodal') }}</span>
            <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>
    <div class="p-4 space-y-4">
        @foreach($locales as $localeKey => $localeData)
        @if($localeKey === current_locale())
        @continue
        @endif
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
                class="input-text w-full">{{ $fieldValue[$localeKey] ?? '' }}</textarea>
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