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

@extends('admin.settings.sidebar')
@section('title', __('personalization.theme.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('personalization.theme.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('personalization.theme.description') }}
        </p>

        <div class="alert bg-primary text-dark dark:text-white mt-2" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            {!! __('personalization.theme.info', ['link' => route('admin.settings.show', ['card' => 'extensions', 'uuid' => 'extensions'])]) !!}
        </div>

        <p class="mt-2 text-gray-800 dark:text-neutral-400">
            {{ __('personalization.theme.current_theme') }} : <strong>{{ $currentTheme->name }}</strong>
        </p>
    </div>


        <form method="POST" action="{{ route('admin.personalization.config_theme', ['theme' => $currentTheme->uuid]) }}" class="card mt-3" enctype="multipart/form-data">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('admin/shared/input', ['name' => 'theme_home_title', 'label' => __('personalization.home.fields.theme_home_title'), 'value' => setting('theme_home_title', setting('app.name')), 'translatable' => setting_is_saved('theme_home_title')])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'theme_home_subtitle', 'label' => __('personalization.home.fields.theme_home_subtitle'), 'value' => setting('theme_home_subtitle', "Hébergeur français de qualité utilisant la nouvelle version Next Gen de CLIENTXCMS."), 'translatable' => setting_is_saved('theme_home_subtitle')])
                </div>
                <div>
                    @include('admin/shared/file', ['name' => 'theme_home_image', 'label' => __('personalization.home.fields.theme_home_image'), 'canRemove' => true])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'theme_switch_mode', 'label' => __('personalization.theme.fields.theme_switch_mode.title'), 'value' => setting('theme_switch_mode'), 'options' => $modes])
                    @include('admin/shared/checkbox', ['name' => 'theme_header_logo', 'label' => __('personalization.theme.fields.theme_header_logo'), 'checked' => setting('theme_header_logo')])
                </div>
            </div>
            <p class="text-gray-800 dark:text-neutral-400">
                @csrf
                {!! $configHTML !!}
            </p>

            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>

        @include('admin/translations/settings-overlay', ['keys' => [
        'theme_home_title' => 'text',
        'theme_home_subtitle' => 'text',
        'theme_home_title_meta' => 'text',
    ], 'class' => \App\Models\Admin\Setting::class, 'id' => 0])
@endsection
