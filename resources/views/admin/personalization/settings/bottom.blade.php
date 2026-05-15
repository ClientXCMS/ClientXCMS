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
    <script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
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
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary text-sm" id="saveButton"
                    {{ $menus->count() == 0 ? 'disabled' : '' }}>
                    {{ __('global.save') }}
                </button>
                <a class="btn btn-secondary text-sm" href="{{ route('admin.personalization.menulinks.create', ['type' => 'bottom']) }}">
                    <i class="bi bi-plus-lg mr-1"></i>
                    {{ __('personalization.addelement') }}
                </a>
            </div>
        </div>

        @if ($menus->count() === 0)
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="bi bi-menu-button-wide text-4xl mb-2 block"></i>
                <p>{{ __('personalization.menu_links.empty_state') }}</p>
            </div>
        @else
            <ul data-button="#saveButton"
                data-url="{{ route('admin.personalization.menulinks.sort', ['type' => 'bottom']) }}" is="sort-list">
                @foreach ($menus as $menu)
                    <li class="sortable-item {{ $menu->hasChildren() ? 'sortable-parent' : '' }}" id="{{ $menu->id }}">
                        <div class="card bg-white dark:bg-slate-900 dark:border-gray-800">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    {!! $menu->getHtmlIcon() !!}
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $menu->name }}</span>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$menu->link_type] ?? $menu->link_type }}</span>
                                    @if ($menu->badge)
                                        <span
                                            class="text-xs px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">{{ $menu->badge }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    @if ($supportDropDropdown)
                                        <a href="{{ route('admin.personalization.menulinks.create', ['type' => 'bottom', 'parent_id' => $menu->id]) }}"
                                            class="btn-icon text-sm"
                                            title="{{ __('personalization.menu_links.add_child') }}">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $menu->id]) }}"
                                        class="btn-icon text-sm" title="{{ __('global.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $menu->id]) }}"
                                        class="inline confirmation-popup">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn-icon text-sm text-red-500 hover:text-red-700">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if ($menu->children->count() > 0)
                            <ol is="sort-list2">
                                @foreach ($menu->children as $child)
                                    <li class="sortable-item {{ $child->children->count() > 0 ? 'sortable-parent' : '' }}"
                                        id="{{ $child->id }}">
                                        <div class="card bg-white dark:bg-slate-900 dark:border-gray-800 ml-12">
                                            <div class="flex justify-between items-center">
                                                <div class="flex items-center gap-2">
                                                    {!! $child->getHtmlIcon() !!}
                                                    <span
                                                        class="font-semibold text-gray-600 dark:text-gray-400">{{ $child->name }}</span>
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$child->link_type] ?? $child->link_type }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    @if ($supportDropDropdown)
                                                        <a href="{{ route('admin.personalization.menulinks.create', ['type' => 'bottom', 'parent_id' => $child->id]) }}"
                                                            class="btn-icon text-sm"
                                                            title="{{ __('personalization.menu_links.add_child') }}">
                                                            <i class="bi bi-plus-circle"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $child->id]) }}"
                                                        class="btn-icon text-sm" title="{{ __('global.edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST"
                                                        action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $child->id]) }}"
                                                        class="inline confirmation-popup">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn-icon text-sm text-red-500 hover:text-red-700">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($child->children->count() > 0)
                                            <ol is="sort-list2">
                                                @foreach ($child->children as $grandchild)
                                                    <li class="sortable-item" id="{{ $grandchild->id }}">
                                                        <div
                                                            class="card bg-white dark:bg-slate-900 dark:border-gray-800 ml-24">
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center gap-2">
                                                                    {!! $grandchild->getHtmlIcon() !!}
                                                                    <span
                                                                        class="font-semibold text-gray-600 dark:text-gray-400">{{ $grandchild->name }}</span>
                                                                    <span
                                                                        class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$grandchild->link_type] ?? $grandchild->link_type }}</span>
                                                                </div>
                                                                <div class="flex items-center gap-1">
                                                                    <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $grandchild->id]) }}"
                                                                        class="btn-icon text-sm" title="{{ __('global.edit') }}">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <form method="POST"
                                                                        action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $grandchild->id]) }}"
                                                                        class="inline confirmation-popup">
                                                                        @method('DELETE')
                                                                        @csrf
                                                                        <button type="submit"
                                                                            class="btn-icon text-sm text-red-500 hover:text-red-700">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ol>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    </div>

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
                'translatable' => setting_is_saved('theme_footer_description'),
            ])

            <div>
                @include('admin/shared/textarea', [
                    'name' => 'theme_footer_topheberg',
                    'label' => __('personalization.theme.fields.theme_footer_topheberg'),
                    'value' => setting('theme_footer_topheberg'),
                ])
            </div>

            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
    </div>

    @include('admin/translations/settings-overlay', [
        'keys' => ['theme_footer_description' => 'textarea'],
        'class' => \App\Models\Admin\Setting::class,
        'id' => 0,
    ])

@endsection
