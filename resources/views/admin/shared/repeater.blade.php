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

<div class="mt-2" x-data="repeaterComponent{{ $rand }}()">
    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(items)">

    <div class="space-y-3">
        <template x-for="(item, index) in items" :key="index">
            <div class="relative bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <button type="button"
                        @click="removeItem(index)"
                        x-show="items.length > {{ $minItems }}"
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
                                    x-model="item.{{ $subfieldKey }}"
                                    rows="{{ $subfield['rows'] ?? 2 }}"
                                    class="input-text text-sm"
                                    placeholder="{{ $subfield['placeholder'] ?? '' }}"
                                ></textarea>
                            @elseif(($subfield['type'] ?? 'text') === 'select')
                                <select x-model="item.{{ $subfieldKey }}" class="input-text text-sm">
                                    @foreach($subfield['options'] ?? [] as $optKey => $optLabel)
                                        <option value="{{ is_numeric($optKey) ? $optLabel : $optKey }}">
                                            {{ is_array($optLabel) ? ($optLabel['label'] ?? $optKey) : $optLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif(($subfield['type'] ?? 'text') === 'icon')
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <i :class="item.{{ $subfieldKey }} || 'bi-star'"></i>
                                    </span>
                                    <input type="text"
                                           x-model="item.{{ $subfieldKey }}"
                                           class="input-text text-sm flex-1"
                                           placeholder="bi-star">
                                </div>
                            @else
                                <input type="{{ $subfield['type'] ?? 'text' }}"
                                       x-model="item.{{ $subfieldKey }}"
                                       class="input-text text-sm"
                                       placeholder="{{ $subfield['placeholder'] ?? '' }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="button"
                            @click="moveItem(index, -1)"
                            :disabled="index === 0"
                            class="text-gray-500 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button"
                            @click="moveItem(index, 1)"
                            :disabled="index === items.length - 1"
                            class="text-gray-500 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <span class="text-xs text-gray-400 ml-auto" x-text="'#' + (index + 1)"></span>
                </div>
            </div>
        </template>

        <button type="button"
                @click="addItem()"
                :disabled="items.length >= {{ $maxItems }}"
                class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 hover:text-gray-700 hover:border-gray-400 dark:hover:border-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="bi bi-plus-lg mr-2"></i>
            {{ __('admin.repeater.add_item') }}
        </button>
    </div>

    <p class="mt-2 text-xs text-gray-500" x-text="items.length + '/{{ $maxItems }}'"></p>
</div>

@error($name)
<span class="mt-2 text-sm text-red-500">{{ $message }}</span>
@enderror

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('repeaterComponent{{ $rand }}', () => ({
        items: @json($currentValue),
        subfields: @json($subfields),
        maxItems: {{ $maxItems }},

        createEmptyItem() {
            const item = {};
            this.subfields.forEach(sf => {
                item[sf.key] = sf.default || '';
            });
            return item;
        },

        addItem() {
            if (this.items.length < this.maxItems) {
                this.items.push(this.createEmptyItem());
            }
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        moveItem(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < this.items.length) {
                const item = this.items.splice(index, 1)[0];
                this.items.splice(newIndex, 0, item);
            }
        }
    }));
});
</script>
