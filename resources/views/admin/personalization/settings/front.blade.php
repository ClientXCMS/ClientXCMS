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
@section('title', __('personalization.front_menu.title'))
@section('script')
<script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
@endsection
@section('setting')
<div class="card">
    <div class="card-heading">
        <div>
            <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                {{ __('personalization.front_menu.title') }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('personalization.front_menu.description') }}
            </p>
        </div>
        <div class="flex gap-2">
            <button type="button" class="btn btn-primary text-sm" id="saveButton" {{ $menus->count() == 0 ? 'disabled' : '' }}>
                {{ __('global.save') }}
            </button>
            <button type="button" class="btn btn-secondary text-sm" onclick="openMenuDrawerCreate()" data-hs-overlay="#menu-drawer">
                <i class="bi bi-plus-lg mr-1"></i>
                {{ __('personalization.addelement') }}
            </button>
        </div>
    </div>
    @if ($menus->count() === 0)
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <i class="bi bi-menu-button-wide text-4xl mb-2 block"></i>
        <p>{{ __('personalization.menu_links.empty_state') }}</p>
    </div>
    @else
    <ul data-button="#saveButton" data-url="{{ route('admin.personalization.menulinks.sort', ['type' => 'front']) }}" is="sort-list">
        @foreach ($menus as $menu)
        <li class="sortable-item {{ $menu->hasChildren() ? 'sortable-parent' : '' }}" id="{{ $menu->id }}"
            data-menu-id="{{ $menu->id }}"
            data-name="{{ $menu->name }}"
            data-url="{{ $menu->url }}"
            data-icon="{{ $menu->icon }}"
            data-badge="{{ $menu->badge }}"
            data-description="{{ $menu->description }}"
            data-link-type="{{ $menu->link_type }}"
            data-allowed-role="{{ $menu->allowed_role }}"
            data-parent-id="{{ $menu->parent_id }}">
            <div class="card bg-white dark:bg-slate-900 dark:border-gray-800">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        {!! $menu->getHtmlIcon() !!}
                        <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $menu->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$menu->link_type] ?? $menu->link_type }}</span>
                        @if ($menu->badge)
                        <span class="text-xs px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">{{ $menu->badge }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1">
                        @if ($supportDropDropdown)
                        <button type="button" class="btn-icon text-sm" onclick="openMenuDrawerCreate({{ $menu->id }})" data-hs-overlay="#menu-drawer" title="{{ __('personalization.menu_links.add_child') }}">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        @endif
                        <button type="button"
                            class="btn-icon text-sm"
                            onclick="openMenuDrawerEdit(this.closest('li'))"
                            data-hs-overlay="#menu-drawer">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $menu->id]) }}" class="btn-icon text-sm" title="{{ __('global.show') }}">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $menu->id]) }}" class="inline confirmation-popup">
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
                <li class="sortable-item {{ $child->children->count() > 0 ? 'sortable-parent' : '' }}" id="{{ $child->id }}"
                    data-menu-id="{{ $child->id }}"
                    data-name="{{ $child->name }}"
                    data-url="{{ $child->url }}"
                    data-icon="{{ $child->icon }}"
                    data-badge="{{ $child->badge }}"
                    data-description="{{ $child->description }}"
                    data-link-type="{{ $child->link_type }}"
                    data-allowed-role="{{ $child->allowed_role }}"
                    data-parent-id="{{ $child->parent_id }}">
                    <div class="card bg-white dark:bg-slate-900 dark:border-gray-800 ml-12">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                {!! $child->getHtmlIcon() !!}
                                <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $child->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$child->link_type] ?? $child->link_type }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if ($supportDropDropdown)
                                <button type="button" class="btn-icon text-sm" onclick="openMenuDrawerCreate({{ $child->id }})" data-hs-overlay="#menu-drawer" title="{{ __('personalization.menu_links.add_child') }}">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                @endif
                                <button type="button"
                                    class="btn-icon text-sm"
                                    onclick="openMenuDrawerEdit(this.closest('li'))"
                                    data-hs-overlay="#menu-drawer">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $child->id]) }}" class="btn-icon text-sm" title="{{ __('global.show') }}">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $child->id]) }}" class="inline confirmation-popup">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="btn-icon text-sm text-red-500 hover:text-red-700">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if ($child->children->count() > 0)
                    <ol is="sort-list2">
                        @foreach ($child->children as $grandchild)
                        <li class="sortable-item" id="{{ $grandchild->id }}"
                            data-menu-id="{{ $grandchild->id }}"
                            data-name="{{ $grandchild->name }}"
                            data-url="{{ $grandchild->url }}"
                            data-icon="{{ $grandchild->icon }}"
                            data-badge="{{ $grandchild->badge }}"
                            data-description="{{ $grandchild->description }}"
                            data-link-type="{{ $grandchild->link_type }}"
                            data-allowed-role="{{ $grandchild->allowed_role }}"
                            data-parent-id="{{ $grandchild->parent_id }}">
                            <div class="card bg-white dark:bg-slate-900 dark:border-gray-800 ml-24">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        {!! $grandchild->getHtmlIcon() !!}
                                        <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $grandchild->name }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $linkTypes[$grandchild->link_type] ?? $grandchild->link_type }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button"
                                            class="btn-icon text-sm"
                                            onclick="openMenuDrawerEdit(this.closest('li'))"
                                            data-hs-overlay="#menu-drawer">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $grandchild->id]) }}" class="btn-icon text-sm" title="{{ __('global.show') }}">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $grandchild->id]) }}" class="inline confirmation-popup">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn-icon text-sm text-red-500 hover:text-red-700">
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

{{-- Drawer for create/edit menu items --}}
<div id="menu-drawer" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
        <h3 id="menu-drawer-title" class="font-bold text-gray-800 dark:text-white">
            {{ __('personalization.addelement') }}
        </h3>
        <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#menu-drawer">
            <span class="sr-only">Close</span>
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="p-4 overflow-y-auto h-[calc(100%-60px)]">
        <div id="menu-drawer-errors" class="hidden alert text-red-700 bg-red-100 mb-4" role="alert"></div>

        <form id="menu-drawer-form" method="POST" action="{{ route('admin.personalization.menulinks.create', ['type' => 'front']) }}">
            @csrf
            <input type="hidden" id="menu-drawer-method" name="_method" value="PUT" disabled>
            <input type="hidden" id="menu-drawer-parent-id" name="parent_id" value="">

            <div class="space-y-4">
                <div>
                    <label for="drawer-link-type" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('personalization.menu_links.link_type') }}</label>
                    <div class="mt-2">
                        <select name="link_type" id="drawer-link-type" class="input-text">
                            @foreach ($linkTypes as $ltValue => $ltLabel)
                            <option value="{{ $ltValue }}">{{ $ltLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-2">
                    @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'id' => 'drawer-name'])
                </div>

                <div class="mt-2">
                    @include('admin/shared/input', ['name' => 'url', 'label' => __('global.url'), 'id' => 'drawer-url'])
                </div>

                <div class="mt-2">
                    @include('admin/shared/input', ['name' => 'icon', 'label' => __('personalization.icon'), 'id' => 'drawer-icon', 'attributes' => ['placeholder' => 'bi bi-house-door']])
                </div>

                <div class="mt-2">
                    @include('admin/shared/input', ['name' => 'badge', 'label' => __('personalization.badge'), 'id' => 'drawer-badge'])
                </div>

                <div>
                    <label for="drawer-allowed-role" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('personalization.menu_links.allowed_role') }}</label>
                    <div class="mt-2">
                        <select name="allowed_role" id="drawer-allowed-role" class="input-text">
                            @foreach ($roles as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-2">
                    @include('admin/shared/input', ['name' => 'description', 'label' => __('global.description'), 'id' => 'drawer-description'])
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="menu-drawer-submit" class="btn btn-primary">
                    {{ __('global.save') }}
                </button>
                <button type="button" class="btn btn-secondary" data-hs-overlay="#menu-drawer">
                    {{ __('global.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

@php $validationErrors = $errors->any() ? $errors->all() : []; @endphp
<script>
    var menuDrawerForm = document.getElementById('menu-drawer-form');
    var menuDrawerMethod = document.getElementById('menu-drawer-method');
    var menuDrawerParentId = document.getElementById('menu-drawer-parent-id');
    var menuDrawerTitle = document.getElementById('menu-drawer-title');
    var menuDrawerErrors = document.getElementById('menu-drawer-errors');
    var storeUrl = "{{ route('admin.personalization.menulinks.create', ['type' => 'front']) }}";
    var updateUrlTemplate = "{{ route('admin.personalization.menulinks.update', ['menulink' => '__ID__']) }}";

    function clearDrawerErrors() {
        menuDrawerErrors.classList.add('hidden');
        menuDrawerErrors.textContent = '';
    }

    function showDrawerErrors(messages) {
        menuDrawerErrors.textContent = '';
        messages.forEach(function(msg) {
            var p = document.createElement('p');
            p.textContent = msg;
            menuDrawerErrors.appendChild(p);
        });
        menuDrawerErrors.classList.remove('hidden');
    }

    function updateDropdownOptionState(hasParent) {
        var linkTypeSelect = document.getElementById('drawer-link-type');
        var dropdownOption = linkTypeSelect.querySelector('option[value="dropdown"]');
        if (dropdownOption) {
            dropdownOption.disabled = hasParent;
            if (hasParent && linkTypeSelect.value === 'dropdown') {
                linkTypeSelect.value = 'link';
            }
        }
    }

    function openMenuDrawerCreate(parentId) {
        clearDrawerErrors();
        menuDrawerForm.action = storeUrl;
        menuDrawerMethod.disabled = true;
        menuDrawerParentId.value = parentId || '';
        menuDrawerTitle.textContent = "{{ __('personalization.addelement') }}";

        document.getElementById('drawer-link-type').value = 'link';
        document.getElementById('drawer-name').value = '';
        document.getElementById('drawer-url').value = '';
        document.getElementById('drawer-icon').value = '';
        document.getElementById('drawer-badge').value = '';
        document.getElementById('drawer-allowed-role').value = 'all';
        document.getElementById('drawer-description').value = '';

        updateDropdownOptionState(!!parentId);
    }

    function openMenuDrawerEdit(li) {
        clearDrawerErrors();
        var dataset = li.dataset;

        menuDrawerForm.action = updateUrlTemplate.replace('__ID__', dataset.menuId);
        menuDrawerMethod.value = 'PUT';
        menuDrawerMethod.disabled = false;
        menuDrawerParentId.value = dataset.parentId || '';
        menuDrawerTitle.textContent = "{{ __('global.edit') }}: " + dataset.name;

        var hasParent = !!dataset.parentId;
        updateDropdownOptionState(hasParent);

        document.getElementById('drawer-link-type').value = dataset.linkType || 'link';
        document.getElementById('drawer-name').value = dataset.name || '';
        document.getElementById('drawer-url').value = dataset.url || '';
        document.getElementById('drawer-icon').value = dataset.icon || '';
        document.getElementById('drawer-badge').value = dataset.badge || '';
        document.getElementById('drawer-allowed-role').value = dataset.allowedRole || 'all';
        document.getElementById('drawer-description').value = dataset.description || '';
    }

    @if($errors - > any())
    window.addEventListener('load', function() {
        showDrawerErrors(@json($validationErrors));

        var overlay = document.getElementById('menu-drawer');
        if (typeof HSOverlay !== 'undefined') {
            HSOverlay.open(overlay);
        }

        @if(old('name'))
        document.getElementById('drawer-name').value = "{{ old('name') }}";
        @endif
        @if(old('url'))
        document.getElementById('drawer-url').value = "{{ old('url') }}";
        @endif
        @if(old('icon'))
        document.getElementById('drawer-icon').value = "{{ old('icon') }}";
        @endif
        @if(old('badge'))
        document.getElementById('drawer-badge').value = "{{ old('badge') }}";
        @endif
        @if(old('link_type'))
        document.getElementById('drawer-link-type').value = "{{ old('link_type') }}";
        @endif
        @if(old('allowed_role'))
        document.getElementById('drawer-allowed-role').value = "{{ old('allowed_role') }}";
        @endif
        @if(old('description'))
        document.getElementById('drawer-description').value = "{{ old('description') }}";
        @endif
        @if(old('parent_id'))
        menuDrawerParentId.value = "{{ old('parent_id') }}";
        @endif
    });
    @endif
</script>
@endsection