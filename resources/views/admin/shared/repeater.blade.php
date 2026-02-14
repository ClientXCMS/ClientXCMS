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
 * Year: 2026
 */
?>

@php
$rand = rand(1, 999);
$subfields = $fields ?? [];
$currentValue = is_array($value ?? null) ? $value : (is_string($value ?? null) ? json_decode($value, true) : []) ?? [];
$minItems = $min ?? 0;
$maxItems = $max ?? 10;
@endphp

@if(isset($label))
<label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}
    @if (isset($help))
    <div class="hs-tooltip inline-block">
        <button type="button" class="hs-tooltip-toggle">
            <i class="bi bi-info-circle-fill text-gray-500 dark:text-gray-400"></i>
            <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white" role="tooltip">
                {{ $help }}
            </span>
        </button>
    </div>
    @endif
</label>
@endif

<div id="repeater-{{ $rand }}" class="mt-2 repeater-component">
    <input type="hidden" name="{{ $name }}" class="repeater-input-value" value="{{ json_encode($currentValue) }}">

    <div class="space-y-3 repeater-list">
        <!-- Javascript will populate this -->
    </div>

    <template class="repeater-template">
        <div class="repeater-item relative bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <button type="button"
                data-action="remove"
                class="absolute top-2 right-2 text-red-500 hover:text-red-700 transition-colors">
                <i class="bi bi-x-circle text-lg"></i>
            </button>

            <div class="grid gap-3 pr-8">
                @foreach($subfields as $subfield)
                @php $subfieldKey = $subfield['key']; @endphp
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        {{ __($subfield['label'] ?? $subfieldKey) }}
                    </label>
                    @if(($subfield['type'] ?? 'text') === 'textarea')
                    <textarea
                        data-field="{{ $subfieldKey }}"
                        rows="{{ $subfield['rows'] ?? 2 }}"
                        class="input-text text-sm"
                        placeholder="{{ $subfield['placeholder'] ?? '' }}"></textarea>
                    @elseif(($subfield['type'] ?? 'text') === 'select')
                    <select data-field="{{ $subfieldKey }}" class="input-text text-sm">
                        @foreach($subfield['options'] ?? [] as $optKey => $optLabel)
                        <option value="{{ is_numeric($optKey) ? $optLabel : $optKey }}">
                            {{ is_array($optLabel) ? ($optLabel['label'] ?? $optKey) : $optLabel }}
                        </option>
                        @endforeach
                    </select>
                    @elseif(($subfield['type'] ?? 'text') === 'icon')
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <i data-icon-preview="{{ $subfieldKey }}" class="bi-star"></i>
                        </span>
                        <input type="text"
                            data-field="{{ $subfieldKey }}"
                            class="input-text text-sm flex-1"
                            placeholder="bi-star">
                    </div>
                    @else
                    <input type="{{ $subfield['type'] ?? 'text' }}"
                        data-field="{{ $subfieldKey }}"
                        class="input-text text-sm"
                        placeholder="{{ $subfield['placeholder'] ?? '' }}">
                    @endif
                </div>
                @endforeach
            </div>

            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <button type="button"
                    data-action="move-up"
                    class="repeater-btn-move-up text-gray-500 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="bi bi-arrow-up"></i>
                </button>
                <button type="button"
                    data-action="move-down"
                    class="repeater-btn-move-down text-gray-500 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="bi bi-arrow-down"></i>
                </button>
                <span class="repeater-index-label text-xs text-gray-400 ml-auto"></span>
            </div>
        </div>
    </template>

    <button type="button"
        data-action="add"
        class="repeater-btn-add w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 hover:text-gray-700 hover:border-gray-400 dark:hover:border-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-3">
        <i class="bi bi-plus-lg mr-2"></i>
        {{ __('global.add') }}
    </button>

    <p class="repeater-count-info mt-2 text-xs text-gray-500"></p>
</div>

@error($name)
<span class="mt-2 text-sm text-red-500">{{ $message }}</span>
@enderror

<script>
    (function() {
        const containerId = 'repeater-{{ $rand }}';

        const initRepeater = () => {
            const container = document.getElementById(containerId);
            if (!container) return;

            const list = container.querySelector('.repeater-list');
            const template = container.querySelector('.repeater-template');
            const output = container.querySelector('.repeater-input-value');
            const countInfo = container.querySelector('.repeater-count-info');
            const addButton = container.querySelector('.repeater-btn-add');

            // Configuration
            const minItems = {{ $minItems }};
            const maxItems = {{ $maxItems }};
            const subfields = @json($subfields);

            // State
            let items = [];
            try {
                items = JSON.parse(output.value || '[]');
            } catch (e) {
                items = [];
            }

            // Helpers
            const updateOutput = () => {
                output.value = JSON.stringify(items);
            };

            const createItemElement = (item, index) => {
                const clone = template.content.cloneNode(true);
                const itemEl = clone.querySelector('.repeater-item');

                itemEl.dataset.index = index;

                // Populate fields
                itemEl.querySelectorAll('[data-field]').forEach(input => {
                    const key = input.dataset.field;
                    if (item[key] !== undefined) {
                        input.value = item[key];
                    }

                    // Trigger initial visual updates if needed (e.g. icons)
                    if (input.dataset.field) {
                        const iconPreview = itemEl.querySelector(`[data-icon-preview="${key}"]`);
                        if (iconPreview) {
                            iconPreview.className = input.value || 'bi-star';
                        }
                    }
                });

                // Update index label
                const label = itemEl.querySelector('.repeater-index-label');
                if (label) label.textContent = '#' + (index + 1);

                // Update button states within item
                const upBtn = itemEl.querySelector('.repeater-btn-move-up');
                const downBtn = itemEl.querySelector('.repeater-btn-move-down');
                const removeBtn = itemEl.querySelector('[data-action="remove"]');

                if (upBtn) upBtn.disabled = index === 0;
                if (downBtn) downBtn.disabled = index === items.length - 1;
                if (removeBtn) removeBtn.style.display = items.length > minItems ? '' : 'none';

                return itemEl;
            };

            const render = () => {
                list.innerHTML = '';
                items.forEach((item, index) => {
                    list.appendChild(createItemElement(item, index));
                });

                // Global UI updates
                if (addButton) addButton.disabled = items.length >= maxItems;
                if (countInfo) countInfo.textContent = `${items.length}/${maxItems}`;
            };

            // Event Handlers
            const handleAdd = () => {
                if (items.length >= maxItems) return;

                const newItem = {};
                subfields.forEach(field => {
                    newItem[field.key] = field.default || '';
                });

                items.push(newItem);
                render();
                updateOutput();
            };

            const handleRemove = (index) => {
                if (items.length <= minItems) return;

                items.splice(index, 1);
                render();
                updateOutput();
            };

            const handleMove = (index, direction) => {
                const newIndex = index + direction;
                if (newIndex < 0 || newIndex >= items.length) return;

                const item = items.splice(index, 1)[0];
                items.splice(newIndex, 0, item);

                render();
                updateOutput();
            };

            const handleInput = (target) => {
                const itemEl = target.closest('.repeater-item');
                if (!itemEl) return;

                const index = parseInt(itemEl.dataset.index);
                const key = target.dataset.field;

                if (!isNaN(index) && key) {
                    items[index][key] = target.value;
                    updateOutput();

                    // Update icon preview if applicable
                    const iconPreview = itemEl.querySelector(`[data-icon-preview="${key}"]`);
                    if (iconPreview) {
                        iconPreview.className = target.value || 'bi-star';
                    }
                }
            };

            // Listeners
            if (addButton) {
                addButton.addEventListener('click', handleAdd);
            }

            container.addEventListener('click', (e) => {
                const target = e.target.closest('[data-action]');
                if (!target) return;

                const action = target.dataset.action;
                const itemEl = target.closest('.repeater-item');
                const index = itemEl ? parseInt(itemEl.dataset.index) : -1;

                if (action === 'remove' && index !== -1) {
                    handleRemove(index);
                } else if (action === 'move-up' && index !== -1) {
                    handleMove(index, -1);
                } else if (action === 'move-down' && index !== -1) {
                    handleMove(index, 1);
                }
            });

            container.addEventListener('input', (e) => {
                if (e.target.matches('[data-field]')) {
                    handleInput(e.target);
                }
            });

            // Initial render
            render();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRepeater);
        } else {
            initRepeater();
        }
    })();
</script>