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
@section('title', __('personalization.bottom_menu.title'))
@section('script')
    <script src="{{ Vite::asset('resources/global/js/admin/menu-inline-editor.js') }}" type="module"></script>
@endsection
@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.bottom_menu.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('personalization.bottom_menu.description') }}
                </p>
            </div>
            <div>
                <a class="btn btn-secondary text-sm" href="{{ route('admin.personalization.menulinks.create', ['type' => 'bottom']) }}">
                    <i class="bi bi-plus-lg mr-1"></i>
                    {{ __('personalization.addelement') }}
                </a>
            </div>
        </div>

        @include('admin.personalization.menu_links._inline-editor', [
            'menus' => $menus,
            'type' => 'bottom',
            'roles' => $roles,
            'linkTypes' => $linkTypes,
            'supportDropDropdown' => $supportDropDropdown
        ])
    </div>

    {{-- Footer settings form (kept separate) --}}
    <div class="card mt-4">
        <div class="card-heading">
            <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                {{ __('personalization.bottom_menu.footer_settings') }}
            </h4>
        </div>

        <form action="{{ route('admin.personalization.bottom_menu') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin/shared/textarea', [
                'name' => 'theme_footer_description',
                'label' => __('personalization.theme.fields.theme_footer_description'),
                'value' => nl2br(setting('theme_footer_description')),
                'help' => __('personalization.theme.fields.theme_footer_description_help'),
                'translatable' => setting_is_saved('theme_footer_description')
            ])

            <div>
                @include('admin/shared/textarea', [
                    'name' => 'theme_footer_topheberg',
                    'label' => __('personalization.theme.fields.theme_footer_topheberg'),
                    'value' => setting('theme_footer_topheberg')
                ])
            </div>

            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
    </div>

    @include('admin/translations/settings-overlay', [
        'keys' => ['theme_footer_description' => 'textarea'],
        'class' => \App\Models\Admin\Setting::class,
        'id' => 0
    ])
@endsection
