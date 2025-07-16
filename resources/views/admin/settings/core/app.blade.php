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
@extends('admin.settings.sidebar')
@section('title', __('admin.settings.core.app.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.app.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.core.app.description') }}
        </p>

        <form action="{{ route('admin.settings.core.app') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                @include('admin/shared/input', ['name' => 'app_name', 'label' => __('admin.settings.core.app.fields.app_name'), 'value' => setting('app.name')])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'app_env', 'label' => __('admin.settings.core.app.fields.app_env'), 'value' => setting('app.env'), 'options' => ['local' => 'local', 'production' => 'production']])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'app_debug', 'label' => __('admin.settings.core.app.fields.app_debug'), 'value' => setting('app.debug') ? 'true' : 'false', 'options' => ['true' => __('global.enabled'), 'false' => __('global.disabled')]])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'app_timezone', 'label' => __('admin.settings.core.app.fields.app_timezone'), 'value' => setting('app.timezone'), 'options' => $timezones])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'app_default_locale', 'label' => __('admin.settings.core.app.fields.app_locale'), 'value' => setting('app_locale'), 'options' => $locales])
                    @include('admin/shared/checkbox', ['name' => 'app_telemetry', 'label' => __('admin.settings.core.app.fields.app_telemetry'), 'value' => setting('app.telemetry') ? 'true' : 'false'])
                    <p class="text-secondary text-sm">{{ __('admin.settings.core.app.fields.app_telemetry_help') }}</p>
                </div>
                <div>
                    @include('admin/shared/file', ['name' => 'app_logo', 'label' => __('admin.settings.core.app.fields.app_logo'), 'canRemove' => true])
                </div>
                <div>
                    @include('admin/shared/file', ['name' => 'app_favicon', 'label' => __('admin.settings.core.app.fields.app_favicon'), 'canRemove' => true])
                </div>
                <div>
                    @include('admin/shared/file', ['name' => 'app_logo_text', 'label' => __('admin.settings.core.app.fields.app_logotext'), 'canRemove' => true])
                </div>
                    @method('PUT')
            </div>
            <button type="submit" class="btn btn-primary mt-3 ">{{ __('global.save') }}</button>
        </form>
@endsection
