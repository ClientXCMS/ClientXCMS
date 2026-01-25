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

<div
    class="card bg-gray-100 dark:bg-slate-700 dark:border-gray-600 ml-16"
    :class="{ 'opacity-50': grandChild.isDeleted }"
    :data-id="grandChild.id || grandChild._tempId"
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
                        x-model="grandChild.icon"
                        placeholder="{{ __('personalization.icon') }}"
                        class="input-text text-sm"
                    >
                </div>

                {{-- Name --}}
                <div>
                    <input
                        type="text"
                        x-model="grandChild.name"
                        placeholder="{{ __('global.name') }} *"
                        class="input-text text-sm"
                        :class="{ 'border-red-500': !grandChild.name || grandChild.name.trim() === '' }"
                        required
                    >
                </div>

                {{-- URL --}}
                <div>
                    <input
                        type="text"
                        x-model="grandChild.url"
                        placeholder="{{ __('global.url') }} *"
                        class="input-text text-sm"
                        :class="{ 'border-red-500': !grandChild.url || grandChild.url.trim() === '' }"
                        required
                    >
                </div>

                {{-- Role --}}
                <div>
                    <select x-model="grandChild.allowed_role" class="input-text text-sm">
                        <template x-for="(label, value) in roles" :key="value">
                            <option :value="value" x-text="label" :selected="grandChild.allowed_role === value"></option>
                        </template>
                    </select>
                </div>

                {{-- Link Type (no dropdown at level 2) --}}
                <div>
                    <select x-model="grandChild.link_type" class="input-text text-sm">
                        <template x-for="(label, value) in getLinkTypes(2)" :key="value">
                            <option :value="value" x-text="label" :selected="grandChild.link_type === value"></option>
                        </template>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <input
                        type="text"
                        x-model="grandChild.description"
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
                @click="openTranslationModal(grandChild)"
                class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-show="grandChild.id"
            >
                <i class="bi bi-translate mr-1"></i>
                {{ __('admin.locales.translate') }}
            </button>

            {{-- No add child button at level 3 --}}
            <span class="text-xs text-gray-400 dark:text-gray-500 italic">
                {{ __('personalization.menu_links.max_depth') }}
            </span>

            {{-- Delete button --}}
            <button
                type="button"
                @click="deleteItem(childItem.children, grandChildIndex)"
                x-show="!grandChild.isDeleted"
                class="text-xs px-2 py-1 rounded border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
            >
                <i class="bi bi-trash mr-1"></i>
                {{ __('global.delete') }}
            </button>

            {{-- Restore button --}}
            <button
                type="button"
                @click="restoreItem(grandChild)"
                x-show="grandChild.isDeleted"
                class="text-xs px-2 py-1 rounded border border-green-300 dark:border-green-600 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors"
            >
                <i class="bi bi-arrow-counterclockwise mr-1"></i>
                {{ __('global.restore') }}
            </button>

            {{-- Link to detailed edit --}}
            <a
                x-show="grandChild.id"
                :href="'{{ route('admin.personalization.menulinks.show', ['menulink' => '__ID__']) }}'.replace('__ID__', grandChild.id)"
                class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ml-auto"
                target="_blank"
            >
                <i class="bi bi-box-arrow-up-right mr-1"></i>
                {{ __('personalization.menu_links.full_edit') }}
            </a>
        </div>
    </div>
</div>
