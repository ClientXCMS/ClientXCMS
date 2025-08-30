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
@section('title', __('personalization.seo.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('personalization.seo.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('personalization.seo.description') }}
        </p>

        <form action="{{ route('admin.settings.personalization.seo') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    @include('admin/shared/input', ['label' => __('personalization.seo.fields.description'), 'name' => 'seo_description', 'value' => setting('seo_description')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('personalization.seo.fields.keywords'), 'name' => 'seo_keywords', 'value' => setting('seo_keywords')])
                </div>
            @method('PUT')
                <div>
                    @include('admin/shared/textarea', ['label' => __('personalization.seo.fields.headscripts'), 'name' => 'seo_headscripts', 'value' => setting('seo_headscripts'), 'rows' => 10])
                </div>
                <div>
                    @include('admin/shared/textarea', ['label' => __('personalization.seo.fields.footscripts'), 'name' => 'seo_footscripts', 'value' => setting('seo_footscripts'), 'rows' => 10])
                </div>

                <div>
                    @include('admin/shared/input', ['name' => 'seo_site_title', 'label' => __('personalization.seo.fields.site_title'), 'value' => setting('seo_site_title'), 'help' => __('personalization.seo.fields.site_title_help'), 'translatable' => setting_is_saved('seo_site_title')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('personalization.seo.fields.themecolor'), 'type' => 'color', 'name' => 'seo_themecolor', 'value' => setting('seo_themecolor')])
                </div>
                <div>
                    @include('admin/shared/checkbox', ['label' => __('personalization.seo.fields.disablereferencement'), 'name' => 'seo_disablereferencement', 'value' => setting('seo_disablereferencement', 'false')])
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3 ">{{ __('global.save') }}</button>
        </form>
    @include('admin/translations/settings-overlay', ['keys' => ['site_title' => 'text'], 'class' => \App\Models\Admin\Setting::class, 'id' => 0])

@endsection
