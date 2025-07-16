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
@section('title', __('maintenance.settings.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('maintenance.settings.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('maintenance.settings.description') }}
        </p>

        <form action="{{ route('admin.settings.core.maintenance') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 mt-2">
                <div>
                    @include('admin/shared/checkbox', ['label' => __('maintenance.settings.maintenance_enabled'), 'name' => 'maintenance_enabled', 'checked' => setting('maintenance_enabled')])
                </div>
                <div>
                    @include('admin/shared/textarea', ['label' => __('maintenance.settings.maintenance_message'), 'name' => 'maintenance_message', 'value' => setting('maintenance_message'), 'rows' => 3, 'help' => __('maintenance.settings.maintenance_message_help')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('maintenance.settings.maintenance_url'), 'name' => 'maintenance_url', 'value' => setting('maintenance_url'), 'help' => __('maintenance.settings.maintenance_url_help')])
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    @include('admin/shared/input', ['label' => __('maintenance.settings.maintenance_button_text'), 'name' => 'maintenance_button_text', 'value' => setting('maintenance_button_text')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('maintenance.settings.maintenance_button_url'), 'name' => 'maintenance_button_url', 'value' => setting('maintenance_button_url')])
                </div>
                <div>
                    @include('admin/shared/input', ['label' => __('maintenance.settings.maintenance_button_icon'), 'name' => 'maintenance_button_icon', 'value' => setting('maintenance_button_icon')])
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
@endsection
