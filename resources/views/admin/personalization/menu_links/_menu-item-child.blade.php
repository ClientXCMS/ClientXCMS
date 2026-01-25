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
    $depth = $depth ?? 1;
    $marginClass = match($depth) {
        1 => 'ml-8',
        2 => 'ml-16',
        default => 'ml-16'
    };
@endphp

<div
    class="card bg-gray-50 dark:bg-slate-800 dark:border-gray-700 {{ $marginClass }}"
    :class="{ 'opacity-50': childItem.isDeleted }"
    :data-id="childItem.id || childItem._tempId"
>
    <div class="p-3">
        {{-- Main row: drag handle + fields --}}
        <div class="flex items-start gap-2">
            {{-- Drag handle --}}
            <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 pt-2">
                <i class="bi bi-grip-vertical text-lg"></i>
            </div>

            {{-- Fields grid --}}
            <div class="flex-1 grid grid-cols-1 md:grid-cols-6 gap-2">
                {{-- Icon --}}
                <div>
                    <input
                        type="text"
                        x-model="childItem.icon"
                        placeholder="{{ __('personalization.icon') }}"
                        class="input-text text-sm"
                    >
                </div>

                {{-- Name --}}
                <div>
                    <input
                        type="text"
                        x-model="childItem.name"
                        placeholder="{{ __('global.name') }} *"
                        class="input-text text-sm"
                        :class="{ 'border-red-500': !childItem.name || childItem.name.trim() === '' }"
                        required
                    >
                </div>

                {{-- URL --}}
                <div>
                    <input
                        type="text"
                        x-model="childItem.url"
                        placeholder="{{ __('global.url') }} *"
                        class="input-text text-sm"
                        :class="{ 'border-red-500': !childItem.url || childItem.url.trim() === '' }"
                        required
                    >
                </div>

                {{-- Role --}}
                <div>
                    <select x-model="childItem.allowed_role" class="input-text text-sm">
                        <template x-for="(label, value) in roles" :key="value">
                            <option :value="value" x-text="label" :selected="childItem.allowed_role === value"></option>
                        </template>
                    </select>
                </div>

                {{-- Link Type --}}
                <div>
                    <select x-model="childItem.link_type" class="input-text text-sm">
                        <template x-for="(label, value) in getLinkTypes({{ $depth }})" :key="value">
                            <option :value="value" x-text="label" :selected="childItem.link_type === value"></option>
                        </template>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <input
                        type="text"
                        x-model="childItem.description"
                        placeholder="{{ __('global.description') }}"
                        class="input-text text-sm"
                    >
                </div>
            </div>
        </div>

        {{-- Actions row --}}
        <div class="flex items-center gap-2 mt-2 ml-6">
            {{-- Translate button --}}
            <button
                type="button"
                @click="openTranslationModal(childItem)"
                class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-show="childItem.id"
            >
                <i class="bi bi-translate mr-1"></i>
                {{ __('admin.locales.translate') }}
            </button>

            {{-- Add grandchild button (only if depth == 1) --}}
            @if($depth == 1)
                <button
                    type="button"
                    @click="addItem(childItem, 2)"
                    class="text-xs px-2 py-1 rounded border border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                >
                    <i class="bi bi-plus mr-1"></i>
                    {{ __('personalization.menu_links.add_child') }}
                </button>
            @endif

            {{-- Delete button --}}
            <button
                type="button"
                @click="deleteItem(item.children, childIndex)"
                x-show="!childItem.isDeleted"
                class="text-xs px-2 py-1 rounded border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
            >
                <i class="bi bi-trash mr-1"></i>
                {{ __('global.delete') }}
            </button>

            {{-- Restore button --}}
            <button
                type="button"
                @click="restoreItem(childItem)"
                x-show="childItem.isDeleted"
                class="text-xs px-2 py-1 rounded border border-green-300 dark:border-green-600 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors"
            >
                <i class="bi bi-arrow-counterclockwise mr-1"></i>
                {{ __('global.restore') }}
            </button>

            {{-- Children count indicator --}}
            @if($depth == 1)
                <span
                    x-show="childItem.children && childItem.children.filter(c => !c.isDeleted).length > 0"
                    class="text-xs text-gray-400 dark:text-gray-500"
                >
                    (<span x-text="childItem.children ? childItem.children.filter(c => !c.isDeleted).length : 0"></span> {{ __('personalization.menu_links.children') }})
                </span>
            @endif

            {{-- Link to detailed edit --}}
            <a
                x-show="childItem.id"
                :href="'{{ route('admin.personalization.menulinks.show', ['menulink' => '__ID__']) }}'.replace('__ID__', childItem.id)"
                class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ml-auto"
                target="_blank"
            >
                <i class="bi bi-box-arrow-up-right mr-1"></i>
                {{ __('personalization.menu_links.full_edit') }}
            </a>
        </div>
    </div>

    {{-- Grandchildren (Level 2) --}}
    @if($depth == 1)
        <div
            x-show="childItem.children && childItem.children.length > 0"
            class="border-t border-gray-200 dark:border-gray-700"
        >
            <div
                data-sortable
                data-depth="2"
                :data-path="index + '.children.' + childIndex + '.children'"
                class="py-2 space-y-2"
            >
                <template x-for="(grandChild, grandChildIndex) in childItem.children" :key="grandChild.id || grandChild._tempId">
                    <div x-show="!grandChild.isDeleted">
                        @include('admin.personalization.menu_links._menu-item-grandchild')
                    </div>
                </template>
            </div>
        </div>
    @endif
</div>
