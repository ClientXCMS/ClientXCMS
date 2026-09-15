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
@section('title', __('personalization.menu_links.title'))
@section('scripts')
    <script>
        document.querySelector('select[name="link_type"]').addEventListener('change', function() {
            const value = this.value;
            if (value == 'dropdown') {
                document.querySelector('input[name="url"]').value = '#';
            }
        });
    </script>
@endsection
@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.menu_links.title') }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('personalization.menu_links.description') }}
                </p>
            </div>
        </div>
        <form action="{{ route($routePath . '.create', ['type' => $type]) }}" method="POST">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf

                <div>
                    @include('admin/shared/select', ['name' => 'link_type', 'label' => __($translatePrefix .'.link_type'), 'options' => $linkTypes, 'value' => old('link_type', $item->link_type)])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'url', 'label' => __('global.url'), 'value' => old('url', $item->url)])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'icon', 'label' => __('personalization.icon'), 'value' => old('icon', $item->icon)])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'badge', 'label' => __('personalization.badge'), 'value' => old('badge', $item->badge)])
                </div>
                @if($supportDropDropdown)
                <div>
                    @include('admin/shared/select', ['name' => 'parent_id', 'label' => __($translatePrefix . '.parent'), 'options' => $menus, 'value' => old('parent_id', $item->parent_id ?? 'none')])
                </div>
                @endif
                <div>
                    @include('admin/shared/select', ['name' => 'allowed_role', 'label' => __($translatePrefix . '.allowed_role'), 'options' => $roles, 'value' => old('allowed_role', $item->allowed_role)])
                </div>
                @if ($item->parent_id)
                    <div>
                        @include('admin/shared/input', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description)])
                    </div>
                @endif
            </div>

            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
    </div>
    </div>
@endsection
