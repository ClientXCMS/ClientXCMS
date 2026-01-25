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
@php
    $locales = \App\Services\Core\LocaleService::getLocales(true, true);
    $menuItems = $menus->map(function($menu) {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'url' => $menu->url,
            'icon' => $menu->icon,
            'badge' => $menu->badge,
            'description' => $menu->description,
            'link_type' => $menu->link_type,
            'allowed_role' => $menu->allowed_role,
            'isNew' => false,
            'isDeleted' => false,
            'translations' => [],
            'children' => $menu->children->map(function($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'url' => $child->url,
                    'icon' => $child->icon,
                    'badge' => $child->badge,
                    'description' => $child->description,
                    'link_type' => $child->link_type,
                    'allowed_role' => $child->allowed_role,
                    'isNew' => false,
                    'isDeleted' => false,
                    'translations' => [],
                    'children' => $child->children->map(function($grandChild) {
                        return [
                            'id' => $grandChild->id,
                            'name' => $grandChild->name,
                            'url' => $grandChild->url,
                            'icon' => $grandChild->icon,
                            'badge' => $grandChild->badge,
                            'description' => $grandChild->description,
                            'link_type' => $grandChild->link_type,
                            'allowed_role' => $grandChild->allowed_role,
                            'isNew' => false,
                            'isDeleted' => false,
                            'translations' => [],
                            'children' => []
                        ];
                    })->values()->toArray()
                ];
            })->values()->toArray()
        ];
    })->values()->toArray();
@endphp

<div
    x-data="menuInlineEditor({
        items: {{ json_encode($menuItems) }},
        type: '{{ $type }}',
        saveUrl: '{{ route('admin.personalization.menulinks.bulk', ['type' => $type]) }}',
        csrfToken: '{{ csrf_token() }}',
        roles: {{ json_encode($roles) }},
        linkTypes: {{ json_encode($linkTypes) }},
        locales: {{ json_encode($locales) }},
        supportDropdown: {{ $supportDropDropdown ? 'true' : 'false' }}
    })"
    x-init="init()"
    class="space-y-4"
>
    {{-- Header with save button --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-2">
            <span x-show="hasChanges" class="text-sm text-amber-600 dark:text-amber-400">
                <i class="bi bi-exclamation-circle mr-1"></i>
                {{ __('personalization.menu_links.unsaved_changes') }}
            </span>
        </div>
        <div class="flex gap-2">
            <button
                type="button"
                @click="save()"
                :disabled="isSaving || items.length === 0"
                class="btn btn-primary"
            >
                <span x-show="isSaving" class="inline-block animate-spin mr-1">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
                <span x-show="!isSaving">
                    <i class="bi bi-check-lg mr-1"></i>
                </span>
                {{ __('global.save') }}
            </button>
        </div>
    </div>

    {{-- Menu items list (Level 0) --}}
    <div
        data-sortable
        data-depth="0"
        data-path=""
        class="space-y-2"
    >
        <template x-for="(item, index) in items" :key="item.id || item._tempId">
            <div x-show="!item.isDeleted">
                @include('admin.personalization.menu_links._menu-item', ['depth' => 0])
            </div>
        </template>
    </div>

    {{-- Add root item button --}}
    <button
        type="button"
        @click="addItem(null, 0)"
        class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 dark:hover:border-blue-400 dark:hover:text-blue-400 transition-colors"
    >
        <i class="bi bi-plus-lg mr-1"></i>
        {{ __('personalization.addelement') }}
    </button>

    {{-- Empty state --}}
    <div
        x-show="items.length === 0"
        class="text-center py-8 text-gray-500 dark:text-gray-400"
    >
        <i class="bi bi-menu-button-wide text-4xl mb-2"></i>
        <p>{{ __('personalization.menu_links.empty_state') }}</p>
    </div>

    {{-- Translation Modal --}}
    @include('admin.personalization.menu_links._translation-modal')
</div>

<script>
    window.menuEditorTranslations = {
        validationError: "{{ __('personalization.menu_links.validation_error') }}",
        saved: "{{ __('personalization.menu_links.bulk_saved') }}",
        saveError: "{{ __('personalization.menu_links.save_error') }}"
    };
</script>
